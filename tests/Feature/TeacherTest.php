<?php

use App\Exceptions\RepositoryException;
use App\Models\Person;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Repositories\TeacherRepository;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function teacherPayload(array $overrides = []): array
{
    return [
        'registration_number' => 'T1001',
        'qualification' => 'Doutorado em Matemática',
        ...$overrides,
    ];
}

function teacherAdmin(?School $school = null): User
{
    return User::factory()->admin()->create(['school_id' => ($school ?? School::factory()->create())->id]);
}

test('guests are redirected to the login page', function () {
    $this->get(route('teacher'))->assertRedirect(route('login'));
});

test('admins can list the teachers of their school and the people with the teacher role available to become one', function () {
    $admin = teacherAdmin();
    $teacher = Teacher::factory()->inSchool($admin->school_id)->create();
    $available = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();
    Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    Person::factory()->inSchool($admin->school_id)->create();
    Person::factory()->inSchool(School::factory()->create()->id)->withRole('teacher')->create();

    $this->actingAs($admin)->get(route('teacher'))->assertInertia(fn ($page) => $page
        ->component('teacher/index')
        ->has('teachers', 1, fn ($row) => $row->where('id', $teacher->id)->has('person.user.email')->etc())
        ->has('people', 1, fn ($row) => $row->where('id', $available->id)->where('name', $available->name)));
});

test('a person with the teacher role becomes a teacher', function () {
    $admin = teacherAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $person->id]))
        ->assertRedirect(route('teacher'));

    expect($person->teacher)->toMatchArray(['registration_number' => 'T1001', 'qualification' => 'Doutorado em Matemática']);
});

test('a person without the teacher role cannot become a teacher', function () {
    $admin = teacherAdmin();
    $student = Person::factory()->inSchool($admin->school_id)->withRole('student')->create();
    $noRole = Person::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $student->id]))
        ->assertSessionHasErrors('person_id');

    $this->post(route('teacher.store'), teacherPayload(['person_id' => $noRole->id]))
        ->assertSessionHasErrors('person_id');

    expect(Teacher::count())->toBe(0);
});

test('linking a person does not change their roles', function () {
    $admin = teacherAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();

    $this->actingAs($admin)->post(route('teacher.store'), teacherPayload(['person_id' => $person->id]));

    expect($person->user->fresh()->getRoleNames()->all())->toBe(['teacher']);
});

test('the qualification is optional', function () {
    $admin = teacherAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $person->id, 'qualification' => null]))
        ->assertRedirect(route('teacher'));

    expect($person->teacher->qualification)->toBeNull();
});

test('a person that is already a teacher is no longer offered', function () {
    $admin = teacherAdmin();
    $teacher = Teacher::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)->get(route('teacher'))
        ->assertInertia(fn ($page) => $page->has('people', 0));

    $this->post(route('teacher.store'), teacherPayload(['person_id' => $teacher->person_id, 'registration_number' => '999']))
        ->assertSessionHasErrors('person_id');
});

test('one person can be both a student and a teacher', function () {
    $admin = teacherAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();
    $student->person->user->assignRole('teacher');

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $student->person_id]))
        ->assertRedirect(route('teacher'));

    expect($student->person->user->fresh()->hasAllRoles(['student', 'teacher']))->toBeTrue();
});

test('registering a teacher validates the payload', function () {
    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), [])
        ->assertSessionHasErrors(['person_id', 'registration_number']);
});

test('a person from another school cannot become a teacher', function () {
    $foreign = Person::factory()->inSchool(School::factory()->create()->id)->withRole('teacher')->create();

    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), teacherPayload(['person_id' => $foreign->id]))
        ->assertSessionHasErrors('person_id');
});

test('the registration number is unique within the school only', function () {
    $admin = teacherAdmin();
    Teacher::factory()->inSchool($admin->school_id)->create(['registration_number' => 'T1001']);
    $person = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $person->id]))
        ->assertSessionHasErrors('registration_number');

    $other = teacherAdmin();
    $otherPerson = Person::factory()->inSchool($other->school_id)->withRole('teacher')->create();

    $this->actingAs($other)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $otherPerson->id]))
        ->assertRedirect(route('teacher'));
});

test('a teacher can be updated without changing their person', function () {
    $admin = teacherAdmin();
    $teacher = Teacher::factory()->inSchool($admin->school_id)->create();
    $personId = $teacher->person_id;

    $this->actingAs($admin)
        ->put(route('teacher.update', $teacher), teacherPayload([
            'person_id' => Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create()->id,
            'registration_number' => 'NEW-1', 'qualification' => 'Mestrado',
        ]))
        ->assertRedirect(route('teacher'));

    expect($teacher->fresh())->registration_number->toBe('NEW-1')
        ->qualification->toBe('Mestrado')
        ->person_id->toBe($personId);
});

test('a school admin cannot see or change teachers from another school', function () {
    $admin = teacherAdmin();
    $foreign = Teacher::factory()->inSchool(School::factory()->create()->id)->create();

    $this->actingAs($admin)->put(route('teacher.update', $foreign), teacherPayload())->assertNotFound();

    $this->actingAs($admin)->get(route('teacher'))
        ->assertInertia(fn ($page) => $page->component('teacher/index')->has('teachers', 0));
});

test('users without the permission cannot access teachers', function () {
    $user = User::factory()->withRole('student')->create();
    $this->actingAs($user);
    $teacher = Teacher::factory()->create();

    $this->get(route('teacher'))->assertForbidden();
    $this->post(route('teacher.store'), teacherPayload())->assertForbidden();
    $this->put(route('teacher.update', $teacher), teacherPayload())->assertForbidden();
});

test('a user granted the permissions can manage teachers', function () {
    $user = User::factory()->withRole('student')->create();
    $user->givePermissionTo(['teachers.view', 'teachers.create']);
    $person = Person::factory()->inSchool($user->school_id)->withRole('teacher')->create();

    $this->actingAs($user)->get(route('teacher'))->assertOk();
    $this->post(route('teacher.store'), teacherPayload(['person_id' => $person->id]))->assertRedirect(route('teacher'));
});

test('no teacher is saved when creating fails midway', function () {
    $admin = teacherAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();
    Teacher::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $person->id]))
        ->assertRedirect(route('teacher'));

    expect(Teacher::count())->toBe(0);
});

test('a friendly error is shown when registering a teacher fails', function () {
    $admin = teacherAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->withRole('teacher')->create();
    $this->mock(TeacherRepository::class)
        ->shouldReceive('create')->andThrow(new RepositoryException('boom'));

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload(['person_id' => $person->id]))
        ->assertRedirect(route('teacher'));

    expect(Teacher::count())->toBe(0);
});
