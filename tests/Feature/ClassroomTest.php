<?php

use App\Exceptions\RepositoryException;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Repositories\ClassroomRepository;
use App\Repositories\ClassroomSubjectRepository;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('classroom'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can list classrooms', function () {
    $this->actingAs(User::factory()->create());
    Classroom::factory()->count(2)->create();

    $response = $this->get(route('classroom'));

    $response->assertOk();
});

test('a classroom can be created', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('classroom.store'), [
        'name' => 'Turma A',
        'school_year' => '2026',
        'shift' => 'morning',
    ]);

    $response->assertRedirect(route('classroom'));
    $this->assertDatabaseHas('classrooms', [
        'name' => 'Turma A',
        'school_year' => '2026',
        'shift' => 'morning',
    ]);
});

test('a classroom requires a valid shift', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('classroom.store'), [
        'name' => 'Turma A',
        'school_year' => '2026',
        'shift' => 'invalid',
    ]);

    $response->assertSessionHasErrors('shift');
});

test('a classroom can be updated', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create([
        'name' => 'Turma A',
        'school_year' => '2026',
        'shift' => 'morning',
    ]);

    $response = $this->put(route('classroom.update', $classroom), [
        'name' => 'Turma B',
        'school_year' => '2027',
        'shift' => 'evening',
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $this->assertDatabaseHas('classrooms', [
        'id' => $classroom->id,
        'name' => 'Turma B',
        'school_year' => '2027',
        'shift' => 'evening',
    ]);
});

test('a friendly error is shown when creating a classroom fails', function () {
    $this->actingAs(User::factory()->create());
    $this->mock(ClassroomRepository::class, function ($mock) {
        $mock->shouldReceive('create')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->post(route('classroom.store'), [
        'name' => 'Turma A',
        'school_year' => '2026',
        'shift' => 'morning',
    ]);

    $response->assertRedirect(route('classroom'));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível criar a turma.')]);
});

test('a friendly error is shown when updating a classroom fails', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();
    $this->mock(ClassroomRepository::class, function ($mock) {
        $mock->shouldReceive('update')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->put(route('classroom.update', $classroom), [
        'name' => 'Turma B',
        'school_year' => '2027',
        'shift' => 'evening',
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar a turma.')]);
});

test('updating a classroom requires a valid shift', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();

    $response = $this->put(route('classroom.update', $classroom), [
        'name' => 'Turma B',
        'school_year' => '2027',
        'shift' => 'invalid',
    ]);

    $response->assertSessionHasErrors('shift');
});

test('a classroom shows its subjects and teachers', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();
    ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->get(route('classroom.show', $classroom));

    $response->assertOk();
});

test('a subject can be added to a classroom with a teacher', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();
    $teacher = Teacher::factory()->create();

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'name' => 'Matemática',
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'teacher_id' => $teacher->id,
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $this->assertDatabaseHas('subjects', ['name' => 'Matemática', 'code' => 'MAT-101']);
    $this->assertDatabaseHas('classroom_subjects', [
        'classroom_id' => $classroom->id,
        'teacher_id' => $teacher->id,
    ]);
});

test('a subject requires an existing teacher', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'name' => 'Matemática',
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'teacher_id' => 999,
    ]);

    $response->assertSessionHasErrors('teacher_id');
});

test('a friendly error is shown when adding a subject fails', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();
    $teacher = Teacher::factory()->create();
    $this->mock(ClassroomSubjectRepository::class, function ($mock) {
        $mock->shouldReceive('createForClassroom')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'name' => 'Matemática',
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'teacher_id' => $teacher->id,
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível adicionar a matéria.')]);
});

test('a subject can be removed from a classroom', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();
    $classroomSubject = ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->delete(route('classroom.subjects.destroy', [$classroom, $classroomSubject]));

    $response->assertRedirect(route('classroom.show', $classroom));
    $this->assertSoftDeleted($classroomSubject);
});

test('a friendly error is shown when removing a subject fails', function () {
    $this->actingAs(User::factory()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();
    $classroomSubject = ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);
    $this->mock(ClassroomSubjectRepository::class, function ($mock) {
        $mock->shouldReceive('delete')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->delete(route('classroom.subjects.destroy', [$classroom, $classroomSubject]));

    $response->assertRedirect(route('classroom.show', $classroom));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível remover a matéria.')]);
});
