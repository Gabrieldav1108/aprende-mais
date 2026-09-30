<?php

use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

test('users only see data from their own school', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    $courseA = Course::factory()->create(['school_id' => $schoolA->id]);
    Course::factory()->create(['school_id' => $schoolB->id]);
    Classroom::factory()->create(['course_id' => $courseA->id]);
    Classroom::factory()->create();

    $this->actingAs(User::factory()->create(['school_id' => $schoolA->id]));

    expect(Course::count())->toBe(1)
        ->and(Classroom::count())->toBe(1)
        ->and(Classroom::first()->school_id)->toBe($schoolA->id);
});

test('users without a school see every school', function () {
    Course::factory()->count(2)->create();
    Subject::factory()->create();

    $this->actingAs(User::factory()->create());

    expect(Course::count())->toBe(3)->and(Subject::count())->toBe(1);
});

test('records created by a school user receive that school id', function () {
    $school = School::factory()->create();
    $this->actingAs(User::factory()->create(['school_id' => $school->id]));

    $term = AcademicTerm::query()->create([
        'name' => '2030/1', 'year' => 2030, 'number' => 1,
        'starts_at' => '2030-02-01', 'ends_at' => '2030-06-30', 'status' => 'planned',
    ]);

    expect($term->school_id)->toBe($school->id);
});

test('a school user cannot open a classroom from another school', function () {
    $schoolA = School::factory()->create();
    $foreignClassroom = Classroom::factory()->create();

    $this->actingAs(User::factory()->admin()->create(['school_id' => $schoolA->id]))
        ->get(route('classroom.show', $foreignClassroom))
        ->assertNotFound();
});

test('a school user cannot pick teachers or courses from another school', function () {
    $schoolA = School::factory()->create();
    $admin = User::factory()->admin()->create(['school_id' => $schoolA->id]);
    $foreignTeacher = Teacher::factory()->create();
    $payload = classroomPayload();

    $this->actingAs($admin)
        ->post(route('classroom.store'), [...$payload, 'homeroom_teacher_id' => $foreignTeacher->id])
        ->assertSessionHasErrors(['homeroom_teacher_id', 'course_id', 'academic_term_id']);
});
