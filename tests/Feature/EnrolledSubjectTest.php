<?php

use App\Enums\EnrollmentStatus;
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

function offerSubjectNamed(Classroom $classroom, string $name): ClassroomSubject
{
    return ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create(['name' => $name])->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);
}

function enrollInClassroom(Student $student, Classroom $classroom, EnrollmentStatus $status = EnrollmentStatus::Active): void
{
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => $status]);
}

test('a student lists the subjects of the classrooms they are actively enrolled in, by name', function () {
    $classroom = Classroom::factory()->create();
    $student = Student::factory()->create(['person_id' => Person::factory()->inSchool($classroom->school_id)->create()->id]);
    enrollInClassroom($student, $classroom);
    $math = offerSubjectNamed($classroom, 'Matemática');
    $biology = offerSubjectNamed($classroom, 'Biologia');
    $finished = Classroom::factory()->create(['course_id' => $classroom->course_id]);
    enrollInClassroom($student, $finished, EnrollmentStatus::Completed);
    offerSubjectNamed($finished, 'Química');
    offerSubjectNamed(Classroom::factory()->create(['course_id' => $classroom->course_id]), 'Física');

    $this->actingAs($student->person->user)
        ->get(route('enrolled-subject'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('classroom-subject/enrolled')
            ->has('classroomSubjects', 2)
            ->where('classroomSubjects.0.id', $biology->id)
            ->where('classroomSubjects.1.id', $math->id)
            ->where('classroomSubjects.0.classroom.id', $classroom->id)
            ->missing('classroomSubjects.0.teacher.person.cpf_cnpj'));
});

test('users without the enrolled subjects permission cannot list them', function () {
    $this->actingAs(User::factory()->withRole('teacher')->create())
        ->get(route('enrolled-subject'))
        ->assertForbidden();
});
