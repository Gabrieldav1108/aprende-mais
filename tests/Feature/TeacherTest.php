<?php

use App\Exceptions\RepositoryException;
use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
use App\Models\School;
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
        'name' => 'Carlos Lima',
        'email' => 'carlos@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'cpf_cnpj' => '987.654.321-00',
        'birth_date' => '1980-03-10',
        'address' => [
            'zip_code' => '01310-100', 'street' => 'Avenida Paulista', 'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo', 'state' => 'sp', 'number' => '1000', 'complement' => 'Sala 5',
        ],
        'phones' => ['(11) 98888-0000'],
        'emails' => ['carlos.contato@example.com'],
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

test('admins can list the teachers of their school with the data needed to edit them', function () {
    $admin = teacherAdmin();
    $teacher = Teacher::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)->get(route('teacher'))->assertInertia(fn ($page) => $page
        ->component('teacher/index')
        ->has('teachers', 1, fn ($row) => $row->where('id', $teacher->id)->has('person.phones')->has('person.emails')->etc()));
});

test('a teacher is registered with user, person, address, phones and emails', function () {
    $admin = teacherAdmin();

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload())
        ->assertRedirect(route('teacher'));

    $user = User::where('email', 'carlos@example.com')->sole();
    $person = Person::with(['address', 'phones', 'emails'])->where('user_id', $user->id)->sole();

    expect($user->school_id)->toBe($admin->school_id)
        ->and($user->hasRole('teacher'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($person->name)->toBe('Carlos Lima')
        ->and($person->school_id)->toBe($admin->school_id)
        ->and($person->cpf_cnpj)->toBe('98765432100')
        ->and($person->address)->toMatchArray(['zip_code' => '01310100', 'state' => 'SP', 'number' => '1000'])
        ->and($person->phones->pluck('number')->all())->toBe(['11988880000'])
        ->and($person->emails->pluck('address')->all())->toBe(['carlos.contato@example.com'])
        ->and($person->teacher)->toMatchArray(['registration_number' => 'T1001', 'qualification' => 'Doutorado em Matemática']);
});

test('a teacher can be registered without address, phones, emails and qualification', function () {
    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), teacherPayload(['address' => [], 'phones' => [], 'emails' => [], 'qualification' => null]))
        ->assertRedirect(route('teacher'));

    $person = Person::with('address')->where('name', 'Carlos Lima')->sole();

    expect($person->address)->toBeNull()
        ->and($person->phones)->toHaveCount(0)
        ->and($person->teacher->qualification)->toBeNull();
});

test('registering a teacher validates the payload', function () {
    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), teacherPayload([
            'email' => 'invalid', 'cpf_cnpj' => '123', 'password' => 'short', 'registration_number' => null,
            'phones' => ['12'], 'emails' => ['not-an-email'],
        ]))
        ->assertSessionHasErrors(['email', 'cpf_cnpj', 'password', 'registration_number', 'phones.0', 'emails.0']);
});

test('an address needs every required field once any is filled', function () {
    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), teacherPayload(['address' => ['zip_code' => '01310100', 'street' => 'Avenida Paulista']]))
        ->assertSessionHasErrors(['address.neighborhood', 'address.city', 'address.state', 'address.number']);
});

test('registration number and document are unique within the school only', function () {
    $admin = teacherAdmin();
    $existing = Teacher::factory()->inSchool($admin->school_id)->create(['registration_number' => 'T1001']);
    $existing->person->update(['cpf_cnpj' => '98765432100']);

    $this->actingAs($admin)
        ->post(route('teacher.store'), teacherPayload())
        ->assertSessionHasErrors(['registration_number', 'cpf_cnpj']);

    $other = teacherAdmin();

    $this->actingAs($other)
        ->post(route('teacher.store'), teacherPayload())
        ->assertRedirect(route('teacher'));
});

test('a teacher can be updated and contacts are replaced', function () {
    $admin = teacherAdmin();
    $teacher = Teacher::factory()->inSchool($admin->school_id)->create();
    Phone::factory()->count(2)->create(['person_id' => $teacher->person_id]);
    Email::factory()->create(['person_id' => $teacher->person_id]);

    $this->actingAs($admin)
        ->put(route('teacher.update', $teacher), teacherPayload([
            'name' => 'Novo Nome', 'email' => 'novo@example.com', 'qualification' => 'Mestrado',
            'registration_number' => $teacher->registration_number, 'phones' => ['11977776666'], 'emails' => [],
        ]))
        ->assertRedirect(route('teacher'));

    $person = $teacher->person->fresh(['user', 'address', 'phones', 'emails']);

    expect($person->name)->toBe('Novo Nome')
        ->and($person->user->name)->toBe('Novo Nome')
        ->and($person->user->email)->toBe('novo@example.com')
        ->and($person->address->street)->toBe('Avenida Paulista')
        ->and($person->phones->pluck('number')->all())->toBe(['11977776666'])
        ->and($person->emails)->toHaveCount(0)
        ->and($teacher->fresh()->qualification)->toBe('Mestrado');
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

    $this->actingAs($user)->get(route('teacher'))->assertOk();
    $this->post(route('teacher.store'), teacherPayload())->assertRedirect(route('teacher'));
});

test('nothing is saved when registering a teacher fails midway', function () {
    Teacher::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), teacherPayload())
        ->assertRedirect(route('teacher'));

    $this->assertDatabaseMissing('users', ['email' => 'carlos@example.com']);
    $this->assertDatabaseCount('people', 0);
    $this->assertDatabaseCount('addresses', 0);
    $this->assertDatabaseCount('phones', 0);
});

test('a friendly error is shown when registering a teacher fails', function () {
    $this->mock(TeacherRepository::class)
        ->shouldReceive('create')->andThrow(new RepositoryException('boom'));

    $this->actingAs(teacherAdmin())
        ->post(route('teacher.store'), teacherPayload())
        ->assertRedirect(route('teacher'));

    $this->assertDatabaseMissing('users', ['email' => 'carlos@example.com']);
});
