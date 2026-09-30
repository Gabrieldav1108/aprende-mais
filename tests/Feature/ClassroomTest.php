<?php

use App\Enums\ClassroomStatus;
use App\Enums\Shift;
use App\Exceptions\RepositoryException;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Course;
use App\Models\Student;
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
    $this->actingAs(User::factory()->admin()->create());
    $payload = classroomPayload();

    $response = $this->post(route('classroom.store'), $payload);

    $response->assertRedirect(route('classroom'));
    $this->assertDatabaseHas('classrooms', [
        'name' => 'Turma A',
        'code' => 'A-2026',
        'course_id' => $payload['course_id'],
        'academic_term_id' => $payload['academic_term_id'],
        'term_number' => 3,
        'shift' => 'morning',
        'status' => 'active',
        'capacity' => 40,
        'school_id' => Course::find($payload['course_id'])->school_id,
    ]);
});

test('a classroom requires a valid shift', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->post(route('classroom.store'), classroomPayload(['shift' => 'invalid']));

    $response->assertSessionHasErrors('shift');
});

test('a classroom cannot be created in a closed academic term', function () {
    $this->actingAs(User::factory()->admin()->create());
    $payload = classroomPayload();
    AcademicTerm::query()->whereKey($payload['academic_term_id'])->update(['status' => 'closed']);

    $response = $this->post(route('classroom.store'), $payload);

    $response->assertSessionHasErrors('academic_term_id');
});

test('a classroom code is unique within the course and term', function () {
    $this->actingAs(User::factory()->admin()->create());
    $existing = Classroom::factory()->create(['code' => 'A-2026']);

    $response = $this->post(route('classroom.store'), classroomPayload([
        'course_id' => $existing->course_id,
        'academic_term_id' => $existing->academic_term_id,
        'code' => 'A-2026',
    ]));

    $response->assertSessionHasErrors('code');
});

test('a classroom can be updated', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create([
        'name' => 'Turma A',
        'shift' => Shift::Morning,
    ]);

    $response = $this->put(route('classroom.update', $classroom), [
        'academic_term_id' => $classroom->academic_term_id,
        'name' => 'Turma B',
        'code' => 'B-2026',
        'term_number' => 4,
        'shift' => 'evening',
        'status' => 'finished',
        'capacity' => 35,
        'room' => 'Sala 202',
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $this->assertDatabaseHas('classrooms', [
        'id' => $classroom->id,
        'name' => 'Turma B',
        'code' => 'B-2026',
        'shift' => 'evening',
        'status' => ClassroomStatus::Finished->value,
        'capacity' => 35,
    ]);
});

test('capacity cannot be lowered below the number of enrolled students', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create(['capacity' => 30]);
    app(ClassroomRepository::class)->enrollStudent($classroom, Student::factory()->create()->id);
    app(ClassroomRepository::class)->enrollStudent($classroom, Student::factory()->create()->id);

    $response = $this->put(route('classroom.update', $classroom), [
        'academic_term_id' => $classroom->academic_term_id,
        'name' => $classroom->name,
        'code' => $classroom->code,
        'term_number' => 1,
        'shift' => 'morning',
        'status' => 'active',
        'capacity' => 1,
    ]);

    $response->assertSessionHasErrors('capacity');
});

test('a friendly error is shown when creating a classroom fails', function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->mock(ClassroomRepository::class, function ($mock) {
        $mock->shouldReceive('create')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->post(route('classroom.store'), classroomPayload());

    $response->assertRedirect(route('classroom'));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível criar a turma.')]);
});

test('a friendly error is shown when updating a classroom fails', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $this->mock(ClassroomRepository::class, function ($mock) {
        $mock->shouldReceive('update')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->put(route('classroom.update', $classroom), [
        'academic_term_id' => $classroom->academic_term_id,
        'name' => 'Turma B',
        'code' => 'B-2026',
        'term_number' => 2,
        'shift' => 'evening',
        'status' => 'active',
        'capacity' => 30,
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar a turma.')]);
});

test('updating a classroom requires a valid shift', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();

    $response = $this->put(route('classroom.update', $classroom), [
        'academic_term_id' => $classroom->academic_term_id,
        'name' => 'Turma B',
        'code' => 'B-2026',
        'term_number' => 2,
        'shift' => 'invalid',
        'status' => 'active',
        'capacity' => 30,
    ]);

    $response->assertSessionHasErrors('shift');
});

test('a classroom shows its subjects and teachers', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);
    $teacher = Teacher::factory()->create();
    ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->get(route('classroom.show', $classroom));

    $response->assertOk();
});

test('a course subject can be added to a classroom with a teacher', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);
    $teacher = Teacher::factory()->create();

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $this->assertDatabaseHas('classroom_subjects', [
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);
});

test('a subject from another course cannot be added to a classroom', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $otherCourseSubject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $otherCourseSubject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response->assertSessionHasErrors('subject_id');
    $this->assertDatabaseCount('classroom_subjects', 0);
});

test('the same subject cannot be offered twice in a classroom', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);
    $teacher = Teacher::factory()->create();
    ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response->assertSessionHasErrors('subject_id');
});

test('a subject requires an existing teacher', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $subject->id,
        'teacher_id' => 999,
    ]);

    $response->assertSessionHasErrors('teacher_id');
});

test('a friendly error is shown when adding a subject fails', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);
    $teacher = Teacher::factory()->create();
    $this->mock(ClassroomSubjectRepository::class, function ($mock) {
        $mock->shouldReceive('attachToClassroom')->andThrow(new RepositoryException('boom'));
    });

    $response = $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);

    $response->assertRedirect(route('classroom.show', $classroom));
    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => __('Não foi possível adicionar a matéria.')]);
});

test('the repository rejects a subject from another course', function () {
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();

    app(ClassroomSubjectRepository::class)->attachToClassroom($classroom, $subject->id, $teacher->id);
})->throws(RepositoryException::class);

test('a subject can be removed from a classroom', function () {
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);
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
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);
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

test('enrollment respects the classroom capacity', function () {
    $classroom = Classroom::factory()->create(['capacity' => 1]);
    $repository = app(ClassroomRepository::class);

    $repository->enrollStudent($classroom, Student::factory()->create()->id);

    expect(fn () => $repository->enrollStudent($classroom, Student::factory()->create()->id))
        ->toThrow(RepositoryException::class, 'Classroom is full.');
    $this->assertDatabaseCount('classroom_students', 1);
});
