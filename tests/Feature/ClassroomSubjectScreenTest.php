<?php

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Attachment;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Person;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function offeredSubject(Classroom $classroom): ClassroomSubject
{
    return ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);
}

function enrollStudentNamed(Classroom $classroom, string $name): Student
{
    $student = Student::factory()->create([
        'person_id' => Person::factory()->inSchool($classroom->school_id)->create(['name' => $name])->id,
    ]);
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => 'active']);

    return $student;
}

test('guests are redirected to the login page', function () {
    $classroom = Classroom::factory()->create();

    $this->get(route('classroom.subjects.show', [$classroom, offeredSubject($classroom)]))
        ->assertRedirect(route('login'));
});

test('a classroom subject shows its subject, teacher and the classroom students by name', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $bruno = enrollStudentNamed($classroom, 'Bruno');
    $ana = enrollStudentNamed($classroom, 'Ana');
    enrollStudentNamed(Classroom::factory()->create(['course_id' => $classroom->course_id]), 'Carla');
    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroom->school_id]));

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('classroom-subject/show')
            ->where('classroomSubject.id', $classroomSubject->id)
            ->where('classroomSubject.subject.id', $classroomSubject->subject_id)
            ->where('classroomSubject.teacher.id', $classroomSubject->teacher_id)
            ->where('classroomSubject.classroom.id', $classroom->id)
            ->has('students', 2)
            ->where('students.0.student.id', $ana->id)
            ->where('students.1.student.id', $bruno->id)
            ->has('statuses'));
});

test('a classroom subject of another classroom is not found', function () {
    $classroom = Classroom::factory()->create();
    $otherClassroom = Classroom::factory()->create(['course_id' => $classroom->course_id]);
    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroom->school_id]));

    $this->get(route('classroom.subjects.show', [$classroom, offeredSubject($otherClassroom)]))
        ->assertNotFound();
});

test('users without the classroom view permission cannot open a classroom subject', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $this->actingAs(User::factory()->create(['school_id' => $classroom->school_id]));

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertForbidden();
});

test('a student enrolled in the classroom sees the announcements but not the students', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $student = enrollStudentNamed($classroom, 'Ana');
    $older = Announcement::factory()->forClassroomSubject($classroomSubject)->create(['created_at' => now()->subDay()]);
    $newer = Announcement::factory()->forClassroomSubject($classroomSubject)->create();
    Announcement::factory()->forClassroomSubject(offeredSubject($classroom))->create();
    $this->actingAs($student->person->user);

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('classroom-subject/show')
            ->has('announcements', 2)
            ->where('announcements.0.id', $newer->id)
            ->where('announcements.1.id', $older->id)
            ->where('canManageAnnouncements', false)
            ->missing('students')
            ->missing('statuses')
            ->missing('classroomSubject.teacher.person.cpf_cnpj'));
});

test('pinned announcements are listed first', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $pinned = Announcement::factory()->forClassroomSubject($classroomSubject)->create(['is_pinned' => true, 'created_at' => now()->subWeek()]);
    $newest = Announcement::factory()->forClassroomSubject($classroomSubject)->create();
    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroom->school_id]));

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('announcements.0.id', $pinned->id)
            ->where('announcements.0.is_pinned', true)
            ->where('announcements.1.id', $newest->id)
            ->where('shifts.evening', 'Noite'));
});

test('the subject teacher may manage the announcements', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $this->actingAs(Teacher::find($classroomSubject->teacher_id)->person->user);

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canManageAnnouncements', true)
            ->has('students'));
});

test('a student sees the activities newest first with their files, without the deleted ones', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $student = enrollStudentNamed($classroom, 'Ana');
    $older = Activity::factory()->forClassroomSubject($classroomSubject)->create(['created_at' => now()->subDay()]);
    $newer = Activity::factory()->forClassroomSubject($classroomSubject)->create(['due_at' => now()->addWeek()]);
    Activity::factory()->forClassroomSubject($classroomSubject)->create()->delete();
    Activity::factory()->forClassroomSubject(offeredSubject($classroom))->create();
    $file = Attachment::factory()->uploaded()->create(['original_name' => 'Lista.pdf']);
    $file->attachable()->associate($newer)->save();
    $this->actingAs($student->person->user);

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('activities', 2)
            ->where('activities.0.id', $newer->id)
            ->where('activities.0.attachments.0.id', $file->id)
            ->where('activities.0.attachments.0.original_name', 'Lista.pdf')
            ->missing('activities.0.attachments.0.path')
            ->where('activities.1.id', $older->id)
            ->where('canManageActivities', false));
});

test('the subject teacher may manage the activities', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $this->actingAs(Teacher::find($classroomSubject->teacher_id)->person->user);

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManageActivities', true));
});

test('a student not actively enrolled in the classroom cannot open its subjects', function (string $status) {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $student = enrollStudentNamed($classroom, 'Ana');
    ClassroomStudent::where('student_id', $student->id)->update(['status' => $status]);
    $this->actingAs($student->person->user);

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertNotFound();
})->with(['on_hold', 'completed']);

test('a student of another classroom cannot open the classroom subject', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $student = enrollStudentNamed(Classroom::factory()->create(['course_id' => $classroom->course_id]), 'Ana');
    $this->actingAs($student->person->user);

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertNotFound();
});

test('opening the classroom subject marks its notifications as read', function () {
    $classroom = Classroom::factory()->create();
    $classroomSubject = offeredSubject($classroom);
    $otherSubject = offeredSubject($classroom);
    $user = enrollStudentNamed($classroom, 'Ana')->person->user;
    $user->notify(new AnnouncementPublishedNotification(Announcement::factory()->forClassroomSubject($classroomSubject)->create()));
    $user->notify(new AnnouncementPublishedNotification(Announcement::factory()->forClassroomSubject($otherSubject)->create()));

    $this->actingAs($user)
        ->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertOk();

    expect($user->unreadNotifications()->count())->toBe(1)
        ->and($user->unreadNotifications()->sole()->data['classroom_subject_id'])->toBe($otherSubject->id);
});
