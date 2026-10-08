<?php

use App\Exceptions\RepositoryException;
use App\Models\Course;
use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
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
        'name' => 'Maria Souza',
        'email' => 'maria@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'cpf_cnpj' => '123.456.789-09',
        'birth_date' => '2000-05-20',
        'address' => [
            'zip_code' => '01310-100', 'street' => 'Avenida Paulista', 'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo', 'state' => 'sp', 'number' => '1000', 'complement' => 'Apto 12',
        ],
        'phones' => ['(11) 99999-0000', '1133334444'],
        'emails' => ['contato@example.com'],
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

test('admins can list the students of their school with the data needed to edit them', function () {
    $admin = schoolAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)->get(route('student'))->assertInertia(fn ($page) => $page
        ->component('student/index')
        ->has('students', 1, fn ($row) => $row->where('id', $student->id)->has('person.phones')->has('person.emails')->etc()));
});

test('a student is registered with user, person, address, phones and emails', function () {
    $admin = schoolAdmin();
    $course = Course::factory()->create(['school_id' => $admin->school_id]);

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload(['course_id' => $course->id]))
        ->assertRedirect(route('student'));

    $user = User::where('email', 'maria@example.com')->sole();
    $person = Person::with(['address', 'phones', 'emails'])->where('user_id', $user->id)->sole();

    expect($user->school_id)->toBe($admin->school_id)
        ->and($user->hasRole('student'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($person->name)->toBe('Maria Souza')
        ->and($person->school_id)->toBe($admin->school_id)
        ->and($person->cpf_cnpj)->toBe('12345678909')
        ->and($person->address)->toMatchArray([
            'zip_code' => '01310100', 'state' => 'SP', 'street' => 'Avenida Paulista', 'number' => '1000',
        ])
        ->and($person->phones->pluck('number')->all())->toBe(['11999990000', '1133334444'])
        ->and($person->emails->pluck('address')->all())->toBe(['contato@example.com'])
        ->and($person->student)->toMatchArray(['course_id' => $course->id, 'enrollment_number' => '2026001']);
});

test('a student can be registered without address, phones and emails', function () {
    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload(['address' => [], 'phones' => [], 'emails' => []]))
        ->assertRedirect(route('student'));

    $person = Person::with('address')->where('name', 'Maria Souza')->sole();

    expect($person->address)->toBeNull()
        ->and($person->phones)->toHaveCount(0);
});

test('registering a student validates the payload', function () {
    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload([
            'email' => 'invalid', 'cpf_cnpj' => '123', 'status' => 'x', 'password' => 'short',
            'phones' => ['12'], 'emails' => ['not-an-email'],
        ]))
        ->assertSessionHasErrors(['email', 'cpf_cnpj', 'status', 'password', 'phones.0', 'emails.0']);
});

test('an address needs every required field once any is filled', function () {
    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload(['address' => ['zip_code' => '01310100', 'street' => 'Avenida Paulista']]))
        ->assertSessionHasErrors(['address.neighborhood', 'address.city', 'address.state', 'address.number']);
});

test('enrollment number and document are unique within the school only', function () {
    $admin = schoolAdmin();
    $existing = Student::factory()->inSchool($admin->school_id)->create(['enrollment_number' => '2026001']);
    $existing->person->update(['cpf_cnpj' => '12345678909']);

    $this->actingAs($admin)
        ->post(route('student.store'), studentPayload())
        ->assertSessionHasErrors(['enrollment_number', 'cpf_cnpj']);

    $other = schoolAdmin();
    Student::factory()->inSchool($admin->school_id)->create(['enrollment_number' => '2026002']);

    $this->actingAs($other)
        ->post(route('student.store'), studentPayload())
        ->assertRedirect(route('student'));
});

test('a student cannot be linked to a course from another school', function () {
    $foreignCourse = Course::factory()->create(['school_id' => School::factory()->create()->id]);

    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload(['course_id' => $foreignCourse->id]))
        ->assertSessionHasErrors('course_id');
});

test('a student can be updated and contacts are replaced', function () {
    $admin = schoolAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();
    Phone::factory()->count(2)->create(['person_id' => $student->person_id]);
    Email::factory()->create(['person_id' => $student->person_id]);

    $this->actingAs($admin)
        ->put(route('student.update', $student), studentPayload([
            'name' => 'Novo Nome', 'email' => 'novo@example.com', 'status' => 'locked',
            'enrollment_number' => $student->enrollment_number, 'phones' => ['11988887777'], 'emails' => [],
        ]))
        ->assertRedirect(route('student'));

    $person = $student->person->fresh(['user', 'address', 'phones', 'emails']);

    expect($person->name)->toBe('Novo Nome')
        ->and($person->user->name)->toBe('Novo Nome')
        ->and($person->user->email)->toBe('novo@example.com')
        ->and($person->address->street)->toBe('Avenida Paulista')
        ->and($person->phones->pluck('number')->all())->toBe(['11988887777'])
        ->and($person->emails)->toHaveCount(0)
        ->and($student->fresh()->status->value)->toBe('locked');
});

test('clearing the address on update removes it', function () {
    $admin = schoolAdmin();
    $student = Student::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)->put(route('student.update', $student), studentPayload([
        'enrollment_number' => $student->enrollment_number, 'email' => $student->person->user->email,
    ]));
    expect($student->person->fresh()->address_id)->not->toBeNull();

    $this->put(route('student.update', $student), studentPayload([
        'enrollment_number' => $student->enrollment_number, 'email' => $student->person->user->email, 'address' => [],
    ]));

    expect($student->person->fresh()->address_id)->toBeNull();
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

    $this->actingAs($user)->get(route('student'))->assertOk();
    $this->post(route('student.store'), studentPayload())->assertRedirect(route('student'));
});

test('nothing is saved when registering a student fails midway', function () {
    Student::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload())
        ->assertRedirect(route('student'));

    $this->assertDatabaseMissing('users', ['email' => 'maria@example.com']);
    $this->assertDatabaseCount('people', 0);
    $this->assertDatabaseCount('addresses', 0);
    $this->assertDatabaseCount('phones', 0);
});

test('a friendly error is shown when registering a student fails', function () {
    $this->mock(StudentRepository::class)
        ->shouldReceive('create')->andThrow(new RepositoryException('boom'));

    $this->actingAs(schoolAdmin())
        ->post(route('student.store'), studentPayload())
        ->assertRedirect(route('student'));

    $this->assertDatabaseMissing('users', ['email' => 'maria@example.com']);
});
