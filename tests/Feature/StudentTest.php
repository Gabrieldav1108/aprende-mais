<?php

use App\Exceptions\RepositoryException;
use App\Models\Course;
use App\Models\Person;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Repositories\StudentRepository;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studentPayload(array $overrides = []): array
{
    return [
        'enrollment_number' => '2026001',
        'status' => 'active',
        ...$overrides,
    ];
}

function schoolAdmin(?School $school = null): User
{
    return User::factory()->admin()->create(['school_id' => ($school ?? School::factory()->create())->id]);
}

test('guests are redirected to the login page', function () {
    $this->get(route('student'))->assertRedirect(route('login'));
});

test('admins can list the students of their school and the people with the student role available to become one', function () {
    $admin = schoolAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();
    $available = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();
    Person::factory()->inSchool($admin->school_id)->create();
    Person::factory()->inSchool(School::factory()->create()->id)->withRole('student')->create();

    $this->actingAs($admin)->get(route('student'))->assertInertia(fn ($page) => $page
        ->component('student/index')
        ->has('students', 1, fn ($row) => $row->where('id', $student->id)->has('person.user.email')->etc())
        ->has('people', 1, fn ($row) => $row->where('id', $available->id)->where('name', $available->name)));
});

test('a person with the student role becomes a student', function () {
    $admin = schoolAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    $course = Course::factory()->create(['school_id' => $admin->school_id]);

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['person_id' => $person->id, 'course_id' => $course->id]))
        ->assertRedirect(route('student'));

    expect($person->student)->toMatchArray(['course_id' => $course->id, 'enrollment_number' => '2026001']);
});

test('a person without the student role cannot become a student', function () {
    $admin = schoolAdmin();
    $teacher = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();
    $noRole = Person::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['person_id' => $teacher->id]))
        ->assertSessionHasErrors('person_id');

    $this->post(route('student.store'), studentPayload(['person_id' => $noRole->id]))
        ->assertSessionHasErrors('person_id');

    expect(Student::count())->toBe(0);
});

test('linking a person does not change their roles', function () {
    $admin = schoolAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();

    $this->actingAs($admin)->post(route('student.store'), studentPayload(['person_id' => $person->id]));

    expect($person->user->fresh()->getRoleNames()->all())->toBe(['student']);
});

test('a person that is already a student is no longer offered', function () {
    $admin = schoolAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)->get(route('student'))
        ->assertInertia(fn ($page) => $page->has('people', 0));

    $this->post(route('student.store'), studentPayload(['person_id' => $student->person_id, 'enrollment_number' => '999']))
        ->assertSessionHasErrors('person_id');
});

test('registering a student validates the payload', function () {
    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), ['status' => 'x'])
        ->assertSessionHasErrors(['person_id', 'enrollment_number', 'status']);
});

test('a person from another school cannot become a student', function () {
    $foreign = Person::factory()->inSchool(School::factory()->create()->id)->withRole('student')->create();

    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload(['person_id' => $foreign->id]))
        ->assertSessionHasErrors('person_id');
});

test('the enrollment number is unique within the school only', function () {
    $admin = schoolAdmin();
    Student::factory()->inSchool($admin->school_id)->create(['enrollment_number' => '2026001']);
    $person = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['person_id' => $person->id]))
        ->assertSessionHasErrors('enrollment_number');

    $other = schoolAdmin();
    $otherPerson = Person::factory()->inSchool($other->school_id)->withRole('student')->create();

    $this->actingAs($other)
        ->post(route('student.store'), studentPayload(['person_id' => $otherPerson->id]))
        ->assertRedirect(route('student'));
});

test('a student cannot be linked to a course from another school', function () {
    $admin = schoolAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    $foreignCourse = Course::factory()->create(['school_id' => School::factory()->create()->id]);

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['person_id' => $person->id, 'course_id' => $foreignCourse->id]))
        ->assertSessionHasErrors('course_id');
});

test('a student can be updated without changing their person', function () {
    $admin = schoolAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();
    $personId = $student->person_id;

    $this->actingAs($admin)
        ->put(route('student.update', $student), studentPayload([
            'person_id' => Person::factory()->inSchool($admin->school_id)->withRole('student')->create()->id,
            'enrollment_number' => 'NEW-1', 'status' => 'locked',
        ]))
        ->assertRedirect(route('student'));

    expect($student->fresh())->enrollment_number->toBe('NEW-1')
        ->and($student->fresh()->status->value)->toBe('locked')
        ->and($student->fresh()->person_id)->toBe($personId);
});

test('a school admin cannot see or change students from another school', function () {
    $admin = schoolAdmin();
    $foreign = Student::factory()->inSchool(School::factory()->create()->id)->create();

    $this->actingAs($admin)->put(route('student.update', $foreign), studentPayload())->assertNotFound();

    $this->actingAs($admin)->get(route('student'))
        ->assertInertia(fn ($page) => $page->component('student/index')->has('students', 0));
});

test('users without a school cannot see any student', function () {
    Student::factory()->create();

    $this->actingAs(User::factory()->admin()->create(['school_id' => null]));

    expect(Student::count())->toBe(0);
});

test('users without the permission cannot access students', function () {
    $user = User::factory()->withRole('teacher')->create();
    $this->actingAs($user);
    $student = Student::factory()->create();

    $this->get(route('student'))->assertForbidden();
    $this->post(route('student.store'), studentPayload())->assertForbidden();
    $this->put(route('student.update', $student), studentPayload())->assertForbidden();
});

test('a user granted the permissions can manage students', function () {
    $user = User::factory()->withRole('teacher')->create();
    $user->givePermissionTo(['students.view', 'students.create']);
    $person = Person::factory()->inSchool($user->school_id)->withRole('student')->create();

    $this->actingAs($user)->get(route('student'))->assertOk();
    $this->post(route('student.store'), studentPayload(['person_id' => $person->id]))->assertRedirect(route('student'));
});

test('no student is saved when creating fails midway', function () {
    $admin = schoolAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    Student::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['person_id' => $person->id]))
        ->assertRedirect(route('student'));

    expect(Student::count())->toBe(0);
});

test('a friendly error is shown when registering a student fails', function () {
    $admin = schoolAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    $this->mock(StudentRepository::class)
        ->shouldReceive('create')->andThrow(new RepositoryException('boom'));

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['person_id' => $person->id]))
        ->assertRedirect(route('student'));

    expect(Student::count())->toBe(0);
});
