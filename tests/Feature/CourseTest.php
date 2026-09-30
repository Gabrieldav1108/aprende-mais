<?php

use App\Models\Course;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function coursePayload(array $overrides = []): array
{
    return [
        'name' => 'Engenharia de Software',
        'code' => 'ENG-SW',
        'level' => 'bachelor',
        'duration_terms' => 8,
        'status' => 'active',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('course'))->assertRedirect(route('login'));
});

test('authenticated users can list and view courses', function () {
    $this->actingAs(User::factory()->create());
    $course = Course::factory()->create();

    $this->get(route('course'))->assertOk();
    $this->get(route('course.show', $course))->assertOk();
});

test('a course can be created in the user school', function () {
    $school = School::factory()->create();
    $admin = User::factory()->admin()->create(['school_id' => $school->id]);

    $this->actingAs($admin)
        ->post(route('course.store'), coursePayload())
        ->assertRedirect(route('course'));

    $this->assertDatabaseHas('courses', ['code' => 'ENG-SW', 'school_id' => $school->id, 'level' => 'bachelor']);
});

test('a course requires a valid level and duration', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('course.store'), coursePayload(['level' => 'invalid', 'duration_terms' => 0]))
        ->assertSessionHasErrors(['level', 'duration_terms']);
});

test('a course code is unique within the school', function () {
    $school = School::factory()->create();
    $admin = User::factory()->admin()->create(['school_id' => $school->id]);
    Course::factory()->create(['school_id' => $school->id, 'code' => 'ENG-SW']);

    $this->actingAs($admin)
        ->post(route('course.store'), coursePayload())
        ->assertSessionHasErrors('code');
});

test('the same course code is allowed in another school', function () {
    Course::factory()->create(['code' => 'ENG-SW']);
    $admin = User::factory()->admin()->create(['school_id' => School::factory()->create()->id]);

    $this->actingAs($admin)
        ->post(route('course.store'), coursePayload())
        ->assertRedirect(route('course'));
});

test('a course can be updated', function () {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create(['name' => 'Antigo']);

    $this->put(route('course.update', $course), coursePayload(['name' => 'Novo', 'code' => $course->code]))
        ->assertRedirect(route('course.show', $course));

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Novo']);
});

test('a subject can be added to the course catalog', function () {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create(['duration_terms' => 8]);

    $this->post(route('course.subjects.store', $course), [
        'name' => 'Cálculo I',
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'suggested_term_number' => 1,
    ])->assertRedirect(route('course.show', $course));

    $this->assertDatabaseHas('subjects', [
        'course_id' => $course->id,
        'school_id' => $course->school_id,
        'code' => 'MAT-101',
        'workload_hours' => 80,
    ]);
});

test('a subject code is unique within the course', function () {
    $this->actingAs(User::factory()->admin()->create());
    $subject = Subject::factory()->create(['code' => 'MAT-101']);

    $this->post(route('course.subjects.store', $subject->course), [
        'name' => 'Outra', 'code' => 'MAT-101', 'workload_hours' => 40,
    ])->assertSessionHasErrors('code');
});

test('non-admin users cannot manage courses', function () {
    $course = Course::factory()->create();
    $student = User::factory()->withRole('student')->create();

    $this->actingAs($student)->post(route('course.store'), coursePayload())->assertForbidden();
    $this->actingAs($student)->put(route('course.update', $course), coursePayload())->assertForbidden();
    $this->actingAs($student)->post(route('course.subjects.store', $course), [
        'name' => 'X', 'code' => 'X', 'workload_hours' => 10,
    ])->assertForbidden();
});
