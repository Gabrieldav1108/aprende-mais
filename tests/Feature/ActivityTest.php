<?php

use App\Enums\EnrollmentStatus;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\ActivityPublishedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Storage::fake(Attachment::DISK);
    Storage::disk(Attachment::DISK)->buildTemporaryUrlsUsing(
        fn (string $path) => "https://bucket.test/{$path}?download-signature",
    );
});

/**
 * A subject offered in a new classroom, taught by a new teacher of the same school.
 */
function activitySubject(?Classroom $classroom = null): ClassroomSubject
{
    $classroom ??= Classroom::factory()->create();

    return ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);
}

function activityTeacher(ClassroomSubject $classroomSubject): User
{
    return Teacher::find($classroomSubject->teacher_id)->person->user;
}

function activityStudent(Classroom $classroom, EnrollmentStatus $status = EnrollmentStatus::Active): User
{
    $student = Student::factory()->inSchool($classroom->school_id)->create();
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => $status]);

    return $student->person->user;
}

/**
 * A file the user uploaded and confirmed, with its object in the fake bucket.
 */
function uploadedAttachmentOf(User $uploader): Attachment
{
    $attachment = Attachment::factory()->uploaded()->create(['uploader_id' => $uploader->id]);
    Storage::disk(Attachment::DISK)->put($attachment->path, 'content');

    return $attachment;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function activityPayload(array $overrides = []): array
{
    return [
        'title' => 'Lista de exercícios 1',
        'description' => "Resolva os exercícios 1 a 10.\nEntregue em PDF.",
        'due_at' => '2026-11-05T02:59:00.000Z',
        'send_email' => true,
        ...$overrides,
    ];
}

function activitiesStoreUrl(ClassroomSubject $classroomSubject): string
{
    return route('classroom.subjects.activities.store', [$classroomSubject->classroom_id, $classroomSubject]);
}

function activityUrl(string $action, Activity $activity): string
{
    return route("classroom.subjects.activities.{$action}", [$activity->classroomSubject->classroom_id, $activity->classroom_subject_id, $activity]);
}

test('the subject teacher publishes an activity with supporting files to the actively enrolled students', function () {
    Notification::fake();
    $classroomSubject = activitySubject();
    $classroom = $classroomSubject->classroom;
    $ana = activityStudent($classroom);
    $onHold = activityStudent($classroom, EnrollmentStatus::OnHold);
    $otherClassroom = activityStudent(Classroom::factory()->create(['course_id' => $classroom->course_id]));
    $teacher = activityTeacher($classroomSubject);
    $files = [uploadedAttachmentOf($teacher), uploadedAttachmentOf($teacher)];

    $this->actingAs($teacher)
        ->post(activitiesStoreUrl($classroomSubject), activityPayload(['attachment_ids' => [$files[0]->id, $files[1]->id]]))
        ->assertRedirect(route('classroom.subjects.show', [$classroom, $classroomSubject, 'tab' => 'activities']))
        ->assertInertiaFlash('toast.type', 'success');

    $activity = Activity::sole();

    expect($activity->classroom_subject_id)->toBe($classroomSubject->id)
        ->and($activity->author_id)->toBe($teacher->id)
        ->and($activity->title)->toBe('Lista de exercícios 1')
        ->and($activity->due_at->equalTo(Carbon::parse('2026-11-05 02:59:00', 'UTC')))->toBeTrue()
        ->and($activity->sent_by_email)->toBeTrue()
        ->and($activity->attachments()->pluck('id')->sort()->values()->all())->toBe([$files[0]->id, $files[1]->id]);

    Notification::assertSentTo($ana, ActivityPublishedNotification::class,
        fn (ActivityPublishedNotification $notification, array $channels) => $notification->activity->is($activity)
            && $channels === ['database', 'mail']);
    Notification::assertNotSentTo([$onHold, $otherClassroom, $teacher], ActivityPublishedNotification::class);
});

test('an activity may have no due date, no files and skip the e-mail', function () {
    Notification::fake();
    $classroomSubject = activitySubject();
    $student = activityStudent($classroomSubject->classroom);

    $this->actingAs(activityTeacher($classroomSubject))
        ->post(activitiesStoreUrl($classroomSubject), activityPayload(['due_at' => null, 'send_email' => false]))
        ->assertRedirect();

    expect(Activity::sole())
        ->due_at->toBeNull()
        ->attachments->toBeEmpty();
    Notification::assertSentTo($student, ActivityPublishedNotification::class,
        fn (ActivityPublishedNotification $notification, array $channels) => $channels === ['database']);
});

test('an admin may publish activities', function () {
    Notification::fake();
    $classroomSubject = activitySubject();

    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroomSubject->classroom->school_id]))
        ->post(activitiesStoreUrl($classroomSubject), activityPayload())
        ->assertRedirect();

    expect(Activity::count())->toBe(1);
});

test('only the subject teacher or an admin may publish activities', function (string $who) {
    Notification::fake();
    $classroomSubject = activitySubject();
    $classroom = $classroomSubject->classroom;

    $user = match ($who) {
        'another teacher' => activityTeacher(activitySubject($classroom)),
        'a student' => activityStudent($classroom),
    };

    $this->actingAs($user)
        ->post(activitiesStoreUrl($classroomSubject), activityPayload())
        ->assertForbidden();

    expect(Activity::count())->toBe(0);
    Notification::assertNothingSent();
})->with(['another teacher', 'a student']);

test('an activity requires a title, a description and a valid due date', function (array $payload, string $field) {
    $classroomSubject = activitySubject();

    $this->actingAs(activityTeacher($classroomSubject))
        ->post(activitiesStoreUrl($classroomSubject), activityPayload($payload))
        ->assertSessionHasErrors($field);

    expect(Activity::count())->toBe(0);
})->with([
    'no title' => [['title' => ''], 'title'],
    'no description' => [['description' => ''], 'description'],
    'an invalid due date' => [['due_at' => 'amanhã'], 'due_at'],
]);

test('an activity only takes files the user uploaded that are free to link', function (string $file) {
    $classroomSubject = activitySubject();
    $teacher = activityTeacher($classroomSubject);

    $attachment = match ($file) {
        'of another user' => uploadedAttachmentOf(User::factory()->create()),
        'still pending' => Attachment::factory()->pending()->create(['uploader_id' => $teacher->id]),
        'linked to another activity' => tap(uploadedAttachmentOf($teacher), fn (Attachment $attachment) => $attachment->attachable()
            ->associate(Activity::factory()->forClassroomSubject($classroomSubject)->create())->save()),
    };

    $this->actingAs($teacher)
        ->post(activitiesStoreUrl($classroomSubject), activityPayload(['attachment_ids' => [$attachment->id]]))
        ->assertSessionHasErrors('attachment_ids.0');

    expect(Activity::query()->where('title', 'Lista de exercícios 1')->exists())->toBeFalse();
})->with(['of another user', 'still pending', 'linked to another activity']);

test('the subject teacher edits an activity, keeping, adding and removing files without notifying again', function () {
    Notification::fake();
    $classroomSubject = activitySubject();
    activityStudent($classroomSubject->classroom);
    $teacher = activityTeacher($classroomSubject);
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create(['author_id' => $teacher->id]);
    [$kept, $removed] = [uploadedAttachmentOf($teacher), uploadedAttachmentOf($teacher)];
    $kept->attachable()->associate($activity)->save();
    $removed->attachable()->associate($activity)->save();
    $added = uploadedAttachmentOf($teacher);

    $this->actingAs($teacher)
        ->put(activityUrl('update', $activity), [
            'title' => 'Novo título',
            'description' => 'Nova descrição',
            'due_at' => null,
            'attachment_ids' => [$kept->id, $added->id],
        ])
        ->assertRedirect(route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject, 'tab' => 'activities']));

    expect($activity->fresh())
        ->title->toBe('Novo título')
        ->description->toBe('Nova descrição')
        ->due_at->toBeNull()
        ->and($activity->attachments()->pluck('id')->sort()->values()->all())->toBe([$kept->id, $added->id])
        ->and(Attachment::find($removed->id))->toBeNull();
    Storage::disk(Attachment::DISK)->assertMissing($removed->path);
    Storage::disk(Attachment::DISK)->assertExists($kept->path);
    Notification::assertNothingSent();
});

test('an admin keeps the files the teacher attached when editing the activity', function () {
    $classroomSubject = activitySubject();
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create();
    $file = uploadedAttachmentOf(activityTeacher($classroomSubject));
    $file->attachable()->associate($activity)->save();

    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroomSubject->classroom->school_id]))
        ->put(activityUrl('update', $activity), [
            'title' => $activity->title,
            'description' => $activity->description,
            'attachment_ids' => [$file->id],
        ])
        ->assertSessionHasNoErrors();

    expect($file->fresh()->attachable_id)->toBe($activity->id);
});

test('the subject teacher soft deletes an activity, keeping its files in the bucket', function () {
    $classroomSubject = activitySubject();
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create();
    $file = uploadedAttachmentOf(activityTeacher($classroomSubject));
    $file->attachable()->associate($activity)->save();

    $this->actingAs(activityTeacher($classroomSubject))
        ->delete(activityUrl('destroy', $activity))
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success');

    expect(Activity::withTrashed()->find($activity->id)->trashed())->toBeTrue()
        ->and(Activity::find($activity->id))->toBeNull()
        ->and(Attachment::find($file->id))->not->toBeNull();
    Storage::disk(Attachment::DISK)->assertExists($file->path);
});

test('another teacher or a student cannot edit or delete the activity', function (string $who) {
    $classroomSubject = activitySubject();
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create();

    $this->actingAs(match ($who) {
        'another teacher' => activityTeacher(activitySubject($classroomSubject->classroom)),
        'a student' => activityStudent($classroomSubject->classroom),
    });

    $this->put(activityUrl('update', $activity), ['title' => 'X', 'description' => 'Y'])->assertForbidden();
    $this->delete(activityUrl('destroy', $activity))->assertForbidden();

    expect($activity->fresh())
        ->title->not->toBe('X')
        ->trashed()->toBeFalse();
})->with(['another teacher', 'a student']);

test('an activity of another classroom subject is not found', function () {
    $classroomSubject = activitySubject();
    $activity = Activity::factory()->forClassroomSubject(activitySubject($classroomSubject->classroom))->create();

    $this->actingAs(activityTeacher($classroomSubject))
        ->delete(route('classroom.subjects.activities.destroy', [$classroomSubject->classroom_id, $classroomSubject, $activity]))
        ->assertNotFound();
});

test('a deleted activity is not found', function () {
    $classroomSubject = activitySubject();
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create();
    $activity->delete();

    $this->actingAs(activityTeacher($classroomSubject))
        ->put(activityUrl('update', $activity), ['title' => 'X', 'description' => 'Y'])
        ->assertNotFound();
});

test('an enrolled student downloads the activity files, an outsider does not, and nobody does once it is deleted', function () {
    $classroomSubject = activitySubject();
    $student = activityStudent($classroomSubject->classroom);
    $outsider = activityStudent(Classroom::factory()->create(['course_id' => $classroomSubject->classroom->course_id]));
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create();
    $file = uploadedAttachmentOf(activityTeacher($classroomSubject));
    $file->attachable()->associate($activity)->save();

    $this->actingAs($student)->get(route('attachments.download', $file))->assertRedirect();
    $this->actingAs($outsider)->get(route('attachments.download', $file))->assertForbidden();

    $activity->delete();

    $this->actingAs($student)->get(route('attachments.download', $file->fresh()))->assertForbidden();
});

test('the notification links to the activities tab and describes the activity', function () {
    $classroomSubject = activitySubject();
    $student = activityStudent($classroomSubject->classroom);
    $activity = Activity::factory()->forClassroomSubject($classroomSubject)->create([
        'title' => 'Lista de exercícios 1',
        'due_at' => Carbon::parse('2026-11-05 02:59:00', 'UTC'),
        'sent_by_email' => true,
    ]);
    $notification = new ActivityPublishedNotification($activity);
    $url = route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject->id, 'tab' => 'activities']);

    $mail = $notification->toMail($student);

    expect($notification->toArray($student))->toBe([
        'activity_id' => $activity->id,
        'classroom_subject_id' => $classroomSubject->id,
        'kind' => 'activity',
        'title' => 'Lista de exercícios 1',
        'subject' => $classroomSubject->subject->name,
        'classroom' => $classroomSubject->classroom->name,
        'url' => $url,
    ])
        ->and($mail->subject)->toBe('Nova atividade em '.$classroomSubject->subject->name)
        ->and($mail->introLines)->toContain('Prazo de entrega: 04/11/2026 23:59')
        ->and($mail->actionUrl)->toBe($url);
});

test('the e-mail is skipped for a student who has not accepted the invitation', function () {
    $classroomSubject = activitySubject();
    $unverified = activityStudent($classroomSubject->classroom);
    $unverified->forceFill(['email_verified_at' => null])->save();
    $notification = new ActivityPublishedNotification(
        Activity::factory()->forClassroomSubject($classroomSubject)->create(['sent_by_email' => true]),
    );

    expect($notification->shouldSend($unverified, 'mail'))->toBeFalse()
        ->and($notification->shouldSend($unverified, 'database'))->toBeTrue();
});
