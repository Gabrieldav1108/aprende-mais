<?php

use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Person;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
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
    $this->actingAs(User::factory()->withRole('student')->create(['school_id' => $classroom->school_id]));

    $this->get(route('classroom.subjects.show', [$classroom, $classroomSubject]))
        ->assertForbidden();
});
