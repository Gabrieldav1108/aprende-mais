<?php

use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function subjectPayload(array $overrides = []): array
{
    return [
        'name' => 'Cálculo I',
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'teacher_id' => Teacher::factory()->create()->id,
        ...$overrides,
    ];
}

test('the subjects screen lists the catalog', function () {
    $this->actingAs(User::factory()->admin()->create());
    Subject::factory()->count(2)->create();

    $this->get(route('subject'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subjects/index')
            ->has('subjects', 2)
            ->has('teachers')
            ->has('knowledgeAreas'));
});

test('a subject is registered with its teacher', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('subject.store'), subjectPayload())
        ->assertRedirect(route('subject'));

    $subject = Subject::where('code', 'MAT-101')->sole();

    expect($subject->teacher_id)->not->toBeNull()
        ->and($subject->courses)->toBeEmpty();
});

test('a subject requires a teacher and a unique code', function () {
    $this->actingAs(User::factory()->admin()->create());
    Subject::factory()->create(['code' => 'MAT-101']);

    $this->post(route('subject.store'), subjectPayload(['teacher_id' => null]))
        ->assertSessionHasErrors(['teacher_id', 'code']);
});

test('a subject can be edited', function () {
    $this->actingAs(User::factory()->admin()->create());
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();

    $this->put(route('subject.update', $subject), subjectPayload([
        'name' => 'Novo nome',
        'code' => $subject->code,
        'teacher_id' => $teacher->id,
    ]))->assertRedirect(route('subject'));

    expect($subject->refresh())
        ->name->toBe('Novo nome')
        ->teacher_id->toBe($teacher->id);
});

test('users without permission cannot use the subjects screen', function () {
    $student = User::factory()->withRole('student')->create();
    $this->actingAs($student);
    $subject = Subject::factory()->create();

    $this->actingAs($student)->get(route('subject'))->assertForbidden();
    $this->actingAs($student)->post(route('subject.store'), subjectPayload())->assertForbidden();
    $this->actingAs($student)->put(route('subject.update', $subject), subjectPayload())->assertForbidden();
});

test('each subject lists the classrooms where its teacher is linked to it', function () {
    $classroom = Classroom::factory()->create();
    $otherClassroom = Classroom::factory()->create(['course_id' => $classroom->course_id]);
    $teacher = Teacher::factory()->inSchool($classroom->school_id)->create();
    $anotherTeacher = Teacher::factory()->inSchool($classroom->school_id)->create();
    $subject = Subject::factory()->forCourse($classroom->course_id)->create(['teacher_id' => $teacher->id]);
    $taught = ClassroomSubject::create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
    ClassroomSubject::create(['classroom_id' => $otherClassroom->id, 'subject_id' => $subject->id, 'teacher_id' => $anotherTeacher->id]);

    $this->actingAs($teacher->person->user)
        ->get(route('subject'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('subjects', 1)
            ->has('subjects.0.classroom_subjects', 1)
            ->where('subjects.0.classroom_subjects.0.id', $taught->id)
            ->where('subjects.0.classroom_subjects.0.classroom_id', $classroom->id)
            ->where('subjects.0.classroom_subjects.0.classroom.name', $classroom->name));
});

test('an admin sees every classroom where a subject is offered', function () {
    $classroom = Classroom::factory()->create();
    $otherClassroom = Classroom::factory()->create(['course_id' => $classroom->course_id]);
    $subject = Subject::factory()->forCourse($classroom->course_id)->create();
    foreach ([$classroom, $otherClassroom] as $room) {
        ClassroomSubject::create(['classroom_id' => $room->id, 'subject_id' => $subject->id, 'teacher_id' => $subject->teacher_id]);
    }

    $this->actingAs(User::factory()->admin()->create(['school_id' => $classroom->school_id]))
        ->get(route('subject'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('subjects.0.classroom_subjects', 2));
});
