<?php

use App\Enums\AttachmentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Attachment;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    fakeAttachmentDisk();
});

/**
 * Fake the bucket, answering presigned URLs with predictable fake ones.
 */
function fakeAttachmentDisk(): void
{
    Storage::fake(Attachment::DISK);

    Storage::disk(Attachment::DISK)->buildTemporaryUploadUrlsUsing(
        fn (string $path) => ['url' => "https://bucket.test/{$path}?upload-signature", 'headers' => []],
    );
    Storage::disk(Attachment::DISK)->buildTemporaryUrlsUsing(
        fn (string $path, DateTimeInterface $expiration, array $options) => "https://bucket.test/{$path}?".http_build_query($options),
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function attachmentUploadPayload(array $overrides = []): array
{
    return [
        'name' => 'Lista de exercícios.pdf',
        'mime_type' => 'application/pdf',
        'size' => 120_000,
        ...$overrides,
    ];
}

/**
 * Put a file of the given size where the attachment expects it, as the browser would through the presigned URL.
 */
function putAttachmentObject(Attachment $attachment, ?int $size = null): void
{
    Storage::disk(Attachment::DISK)->put($attachment->path, str_repeat('a', $size ?? $attachment->size));
}

test('a user gets a presigned upload URL for a pending attachment', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('attachments.store'), attachmentUploadPayload())
        ->assertCreated();

    $attachment = Attachment::sole();

    expect($attachment->uploader_id)->toBe($user->id)
        ->and($attachment->status)->toBe(AttachmentStatus::Pending)
        ->and($attachment->disk)->toBe(Attachment::DISK)
        ->and($attachment->original_name)->toBe('Lista de exercícios.pdf')
        ->and($attachment->size)->toBe(120_000)
        ->and($attachment->path)->toStartWith('attachments/')->toEndWith('.pdf');

    $response->assertExactJson([
        'attachment' => ['id' => $attachment->id],
        'upload' => [
            'url' => "https://bucket.test/{$attachment->path}?upload-signature",
            'headers' => ['Content-Type' => 'application/pdf'],
        ],
    ]);
});

test('the stored extension comes from the MIME type, not from the file name', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('attachments.store'), attachmentUploadPayload(['name' => 'foto.php', 'mime_type' => 'image/png']))
        ->assertCreated();

    expect(Attachment::sole()->path)->toEndWith('.png');
});

test('an upload with an invalid file is rejected', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('attachments.store'), attachmentUploadPayload($payload))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(Attachment::count())->toBe(0);
})->with([
    'a type outside the list' => [['mime_type' => 'application/x-msdownload'], 'mime_type'],
    'a file over 20 MB' => [['size' => Attachment::MAX_SIZE + 1], 'size'],
    'an empty file' => [['size' => 0], 'size'],
    'no name' => [['name' => ''], 'name'],
]);

test('guests cannot request an upload URL', function () {
    $this->postJson(route('attachments.store'), attachmentUploadPayload())->assertUnauthorized();
});

test('the uploader confirms an upload that reached the bucket', function () {
    $attachment = Attachment::factory()->pending()->create(['size' => 2_048]);
    putAttachmentObject($attachment);

    $this->actingAs($attachment->uploader)
        ->postJson(route('attachments.complete', $attachment))
        ->assertOk()
        ->assertJsonPath('attachment.id', $attachment->id)
        ->assertJsonPath('attachment.size', 2_048);

    expect($attachment->fresh())
        ->status->toBe(AttachmentStatus::Uploaded)
        ->uploaded_at->not->toBeNull();
});

test('an upload that has not reached the bucket stays pending', function () {
    $attachment = Attachment::factory()->pending()->create();

    $this->actingAs($attachment->uploader)
        ->postJson(route('attachments.complete', $attachment))
        ->assertUnprocessable();

    expect($attachment->fresh()->status)->toBe(AttachmentStatus::Pending);
});

test('an upload whose size differs from the declared one is discarded', function () {
    $attachment = Attachment::factory()->pending()->create(['size' => 1_000]);
    putAttachmentObject($attachment, 5_000);

    $this->actingAs($attachment->uploader)
        ->postJson(route('attachments.complete', $attachment))
        ->assertUnprocessable();

    expect(Attachment::find($attachment->id))->toBeNull();
    Storage::disk(Attachment::DISK)->assertMissing($attachment->path);
});

test('only the uploader confirms a pending upload', function () {
    $attachment = Attachment::factory()->pending()->create();
    putAttachmentObject($attachment);

    $this->actingAs(User::factory()->create())
        ->postJson(route('attachments.complete', $attachment))
        ->assertForbidden();

    expect($attachment->fresh()->status)->toBe(AttachmentStatus::Pending);
});

test('an upload cannot be confirmed twice', function () {
    $attachment = Attachment::factory()->uploaded()->create();
    putAttachmentObject($attachment);

    $this->actingAs($attachment->uploader)
        ->postJson(route('attachments.complete', $attachment))
        ->assertForbidden();
});

test('the uploader downloads an unlinked attachment through a presigned URL with its original name', function () {
    $attachment = Attachment::factory()->uploaded()->create(['original_name' => 'Relatório final.pdf']);

    $response = $this->actingAs($attachment->uploader)
        ->get(route('attachments.download', $attachment))
        ->assertRedirect();

    expect($response->headers->get('Location'))
        ->toStartWith("https://bucket.test/{$attachment->path}?")
        ->and(urldecode($response->headers->get('Location')))
        ->toContain('attachment; filename="Relatorio final.pdf"; filename*=utf-8\'\'Relat%C3%B3rio%20final.pdf');
});

test('another user cannot download an unlinked attachment', function () {
    $attachment = Attachment::factory()->uploaded()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('attachments.download', $attachment))
        ->assertForbidden();
});

test('a pending attachment cannot be downloaded', function () {
    $attachment = Attachment::factory()->pending()->create();

    $this->actingAs($attachment->uploader)
        ->get(route('attachments.download', $attachment))
        ->assertForbidden();
});

test('a linked attachment follows the view permission of the record it belongs to', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);
    $student = Student::factory()->inSchool($classroom->school_id)->create();
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => EnrollmentStatus::Active]);
    $outsider = Student::factory()->inSchool($classroom->school_id)->create();

    $attachment = Attachment::factory()->uploaded()->create();
    $attachment->attachable()->associate($classroomSubject)->save();

    $this->actingAs($student->person->user)
        ->get(route('attachments.download', $attachment))
        ->assertRedirect();

    $this->actingAs($outsider->person->user)
        ->get(route('attachments.download', $attachment))
        ->assertForbidden();
});
