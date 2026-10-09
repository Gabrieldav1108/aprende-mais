<?php

use App\Models\Course;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
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

    $this->from(route('course'))
        ->put(route('course.update', $course), coursePayload(['name' => 'Novo', 'code' => $course->code]))
        ->assertRedirect(route('course'));

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Novo']);
});

test('after updating a course the user returns to the page that opened the modal', function () {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create();

    $this->from(route('course.show', $course))
        ->put(route('course.update', $course), coursePayload(['code' => $course->code]))
        ->assertRedirect(route('course.show', $course));
});

test('a subject can be added to the course catalog', function () {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create(['duration_terms' => 8]);
    $teacher = Teacher::factory()->create();

    $this->post(route('course.subjects.store', $course), [
        'name' => 'Cálculo I',
        'teacher_id' => $teacher->id,
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'suggested_term_number' => 1,
    ])->assertRedirect(route('course.show', $course));

    $this->assertDatabaseHas('subjects', [
        'school_id' => $course->school_id,
        'code' => 'MAT-101',
        'workload_hours' => 80,
    ]);

    $subject = Subject::where('code', 'MAT-101')->sole();

    $this->assertDatabaseHas('course_subject', [
        'course_id' => $course->id,
        'subject_id' => $subject->id,
        'suggested_term_number' => 1,
    ]);
});

test('a subject code is unique within the school', function () {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create();
    Subject::factory()->forCourse($course)->create(['code' => 'MAT-101']);
    $otherCourse = Course::factory()->create();

    $this->post(route('course.subjects.store', $otherCourse), [
        'name' => 'Outra', 'code' => 'MAT-101', 'workload_hours' => 40, 'teacher_id' => Teacher::factory()->create()->id,
    ])->assertSessionHasErrors('code');
});

test('non-admin users cannot manage courses', function () {
    $student = User::factory()->withRole('student')->create();
    $this->actingAs($student);
    $course = Course::factory()->create();

    $this->actingAs($student)->post(route('course.store'), coursePayload())->assertForbidden();
    $this->actingAs($student)->put(route('course.update', $course), coursePayload())->assertForbidden();
    $this->actingAs($student)->post(route('course.subjects.store', $course), [
        'name' => 'X', 'code' => 'X', 'workload_hours' => 10,
    ])->assertForbidden();
});

test('a subject requires a teacher', function () {
    $this->actingAs(User::factory()->admin()->create());
    $course = Course::factory()->create();

    $this->post(route('course.subjects.store', $course), [
        'name' => 'Cálculo I', 'code' => 'MAT-101', 'workload_hours' => 80,
    ])->assertSessionHasErrors('teacher_id');
});

test('a course is created with its subjects and the term of each one', function () {
    $this->actingAs(User::factory()->admin()->create());
    $first = Subject::factory()->create();
    $second = Subject::factory()->create();

    $this->post(route('course.store'), coursePayload(['duration_terms' => 8, 'subjects' => [
        ['id' => $first->id, 'term' => 1],
        ['id' => $second->id, 'term' => 3],
    ]]))->assertRedirect(route('course'));

    $course = Course::where('code', 'ENG-SW')->sole();

    expect($course->subjects->pluck('pivot.suggested_term_number', 'id')->all())
        ->toBe([$first->id => 1, $second->id => 3]);
});

test('a course update replaces its subjects and terms', function () {
    $this->actingAs(User::factory()->admin()->create());
    $kept = Subject::factory()->create();
    $dropped = Subject::factory()->create();
    $course = Course::factory()->create(['duration_terms' => 8]);
    $course->subjects()->attach([$kept->id => ['suggested_term_number' => 2], $dropped->id => ['suggested_term_number' => 4]]);

    $this->put(route('course.update', $course), coursePayload([
        'code' => $course->code,
        'duration_terms' => 8,
        'subjects' => [['id' => $kept->id, 'term' => 5]],
    ]))->assertRedirect();

    expect($course->subjects->pluck('pivot.suggested_term_number', 'id')->all())->toBe([$kept->id => 5]);
});

test('every subject of a course needs a term within its duration', function () {
    $this->actingAs(User::factory()->admin()->create());
    $subject = Subject::factory()->create();

    $this->post(route('course.store'), coursePayload(['duration_terms' => 4, 'subjects' => [['id' => $subject->id]]]))
        ->assertSessionHasErrors('subjects.0.term');

    $this->post(route('course.store'), coursePayload(['duration_terms' => 4, 'subjects' => [['id' => $subject->id, 'term' => 5]]]))
        ->assertSessionHasErrors('subjects.0.term');
});

test('a course cannot be linked to a subject of another school', function () {
    $this->actingAs(User::factory()->admin()->create());
    $foreign = Subject::withoutGlobalScopes()->create(['school_id' => School::factory()->create()->id, 'name' => 'X', 'code' => 'X-1', 'workload_hours' => 10]);

    $this->post(route('course.store'), coursePayload(['subjects' => [['id' => $foreign->id, 'term' => 1]]]))
        ->assertSessionHasErrors('subjects.0.id');
});
