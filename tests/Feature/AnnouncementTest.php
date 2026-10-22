<?php

use App\Enums\EnrollmentStatus;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * A subject offered in a new classroom, taught by a new teacher of the same school.
 */
function subjectWithTeacher(?Classroom $classroom = null): ClassroomSubject
{
    $classroom ??= Classroom::factory()->create();

    return ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);
}

function teacherUserOf(ClassroomSubject $classroomSubject): User
{
    return Teacher::find($classroomSubject->teacher_id)->person->user;
}

function studentUserIn(Classroom $classroom, EnrollmentStatus $status = EnrollmentStatus::Active, array $userState = []): User
{
    $student = Student::factory()->inSchool($classroom->school_id)->create();
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => $status]);

    $user = $student->person->user;
    $user->forceFill($userState)->save();

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function announcementPayload(array $overrides = []): array
{
    return [
        'title' => 'Prova remarcada',
        'body' => "A prova foi remarcada para sexta.\nTragam calculadora.",
        'send_email' => true,
        ...$overrides,
    ];
}

test('the subject teacher publishes an announcement to the actively enrolled students', function () {
    Notification::fake();
    $classroomSubject = subjectWithTeacher();
    $classroom = $classroomSubject->classroom;
    $ana = studentUserIn($classroom);
    $bruno = studentUserIn($classroom);
    $onHold = studentUserIn($classroom, EnrollmentStatus::OnHold);
    $otherClassroom = studentUserIn(Classroom::factory()->create(['course_id' => $classroom->course_id]));
    $teacher = teacherUserOf($classroomSubject);

    $this->actingAs($teacher)
        ->post(route('classroom.subjects.announcements.store', [$classroom, $classroomSubject]), announcementPayload())
        ->assertRedirect(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertInertiaFlash('toast.type', 'success');

    $announcement = Announcement::sole();

    expect($announcement->classroom_subject_id)->toBe($classroomSubject->id)
        ->and($announcement->author_id)->toBe($teacher->id)
        ->and($announcement->title)->toBe('Prova remarcada')
        ->and($announcement->sent_by_email)->toBeTrue();

    Notification::assertSentTo([$ana, $bruno], AnnouncementPublishedNotification::class,
        fn (AnnouncementPublishedNotification $notification, array $channels) => $notification->announcement->is($announcement)
            && $channels === ['database', 'mail']);
    Notification::assertNotSentTo([$onHold, $otherClassroom, $teacher], AnnouncementPublishedNotification::class);
});

test('an announcement without e-mail is only stored in the app', function () {
    Notification::fake();
    $classroomSubject = subjectWithTeacher();
    $student = studentUserIn($classroomSubject->classroom);

    $this->actingAs(teacherUserOf($classroomSubject))
        ->post(route('classroom.subjects.announcements.store', [$classroomSubject->classroom_id, $classroomSubject]), announcementPayload(['send_email' => false]))
        ->assertRedirect();

    Notification::assertSentTo($student, AnnouncementPublishedNotification::class,
        fn (AnnouncementPublishedNotification $notification, array $channels) => $channels === ['database']);
});

test('an admin may publish announcements', function () {
    Notification::fake();
    $classroomSubject = subjectWithTeacher();

    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroomSubject->classroom->school_id]))
        ->post(route('classroom.subjects.announcements.store', [$classroomSubject->classroom_id, $classroomSubject]), announcementPayload())
        ->assertRedirect();

    expect(Announcement::count())->toBe(1);
});

test('only the subject teacher or an admin may publish announcements', function (string $who) {
    Notification::fake();
    $classroomSubject = subjectWithTeacher();
    $classroom = $classroomSubject->classroom;

    $user = match ($who) {
        'another teacher' => teacherUserOf(subjectWithTeacher($classroom)),
        'a student' => studentUserIn($classroom),
    };

    $this->actingAs($user)
        ->post(route('classroom.subjects.announcements.store', [$classroom, $classroomSubject]), announcementPayload())
        ->assertForbidden();

    expect(Announcement::count())->toBe(0);
    Notification::assertNothingSent();
})->with(['another teacher', 'a student']);

test('an announcement requires a title and a body', function (string $field) {
    $classroomSubject = subjectWithTeacher();

    $this->actingAs(teacherUserOf($classroomSubject))
        ->post(route('classroom.subjects.announcements.store', [$classroomSubject->classroom_id, $classroomSubject]), announcementPayload([$field => '']))
        ->assertSessionHasErrors($field);

    expect(Announcement::count())->toBe(0);
})->with(['title', 'body']);

test('the subject teacher edits an announcement without notifying the students again', function () {
    Notification::fake();
    $classroomSubject = subjectWithTeacher();
    studentUserIn($classroomSubject->classroom);
    $announcement = Announcement::factory()->forClassroomSubject($classroomSubject)->create();

    $this->actingAs(teacherUserOf($classroomSubject))
        ->put(route('classroom.subjects.announcements.update', [$classroomSubject->classroom_id, $classroomSubject, $announcement]), [
            'title' => 'Novo título',
            'body' => 'Novo texto',
        ])
        ->assertRedirect(route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject]));

    expect($announcement->fresh())
        ->title->toBe('Novo título')
        ->body->toBe('Novo texto');
    Notification::assertNothingSent();
});

test('an announcement may be pinned when published and unpinned when edited', function () {
    Notification::fake();
    $classroomSubject = subjectWithTeacher();
    $this->actingAs(teacherUserOf($classroomSubject));

    $this->post(route('classroom.subjects.announcements.store', [$classroomSubject->classroom_id, $classroomSubject]), announcementPayload(['is_pinned' => '1']))
        ->assertRedirect();

    $announcement = Announcement::sole();
    expect($announcement->is_pinned)->toBeTrue();

    $this->put(route('classroom.subjects.announcements.update', [$classroomSubject->classroom_id, $classroomSubject, $announcement]), [
        'title' => $announcement->title,
        'body' => $announcement->body,
    ])->assertRedirect();

    expect($announcement->fresh()->is_pinned)->toBeFalse();
});

test('the subject teacher deletes an announcement', function () {
    $classroomSubject = subjectWithTeacher();
    $announcement = Announcement::factory()->forClassroomSubject($classroomSubject)->create();

    $this->actingAs(teacherUserOf($classroomSubject))
        ->delete(route('classroom.subjects.announcements.destroy', [$classroomSubject->classroom_id, $classroomSubject, $announcement]))
        ->assertRedirect();

    expect($announcement->fresh()->trashed())->toBeTrue();
});

test('another teacher cannot edit or delete the announcement', function () {
    $classroomSubject = subjectWithTeacher();
    $announcement = Announcement::factory()->forClassroomSubject($classroomSubject)->create();
    $this->actingAs(teacherUserOf(subjectWithTeacher($classroomSubject->classroom)));

    $this->put(route('classroom.subjects.announcements.update', [$classroomSubject->classroom_id, $classroomSubject, $announcement]), ['title' => 'X', 'body' => 'Y'])
        ->assertForbidden();
    $this->delete(route('classroom.subjects.announcements.destroy', [$classroomSubject->classroom_id, $classroomSubject, $announcement]))
        ->assertForbidden();

    expect($announcement->fresh()->trashed())->toBeFalse();
});

test('an announcement of another classroom subject is not found', function () {
    $classroomSubject = subjectWithTeacher();
    $announcement = Announcement::factory()->forClassroomSubject(subjectWithTeacher($classroomSubject->classroom))->create();

    $this->actingAs(teacherUserOf($classroomSubject))
        ->delete(route('classroom.subjects.announcements.destroy', [$classroomSubject->classroom_id, $classroomSubject, $announcement]))
        ->assertNotFound();
});

test('the notification links to the classroom subject and describes the announcement', function () {
    $classroomSubject = subjectWithTeacher();
    $student = studentUserIn($classroomSubject->classroom);
    $announcement = Announcement::factory()->forClassroomSubject($classroomSubject)->create(['title' => 'Prova remarcada', 'sent_by_email' => true]);
    $notification = new AnnouncementPublishedNotification($announcement);
    $url = route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject->id]);

    $mail = $notification->toMail($student);

    expect($notification->toArray($student))->toBe([
        'announcement_id' => $announcement->id,
        'classroom_subject_id' => $classroomSubject->id,
        'title' => 'Prova remarcada',
        'subject' => $classroomSubject->subject->name,
        'classroom' => $classroomSubject->classroom->name,
        'url' => $url,
    ])
        ->and($mail->subject)->toBe('Novo aviso em '.$classroomSubject->subject->name)
        ->and($mail->actionUrl)->toBe($url);
});

test('the e-mail is skipped for a student who has not accepted the invitation', function () {
    $classroomSubject = subjectWithTeacher();
    $verified = studentUserIn($classroomSubject->classroom);
    $unverified = studentUserIn($classroomSubject->classroom, userState: ['email_verified_at' => null]);
    $notification = new AnnouncementPublishedNotification(
        Announcement::factory()->forClassroomSubject($classroomSubject)->create(['sent_by_email' => true]),
    );

    expect($notification->shouldSend($verified, 'mail'))->toBeTrue()
        ->and($notification->shouldSend($unverified, 'mail'))->toBeFalse()
        ->and($notification->shouldSend($unverified, 'database'))->toBeTrue();
});
