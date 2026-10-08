<?php

use App\Exceptions\RepositoryException;
use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
use App\Models\School;
use App\Models\User;
use App\Repositories\PersonRepository;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userPayload(array $overrides = []): array
{
    return [
        'name' => 'Ana Prado',
        'email' => 'ana@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'cpf_cnpj' => '111.222.333-96',
        'birth_date' => '1995-08-01',
        'address' => [
            'zip_code' => '01310-100', 'street' => 'Avenida Paulista', 'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo', 'state' => 'sp', 'number' => '1000', 'complement' => 'Apto 12',
        ],
        'phones' => ['(11) 99999-0000', '1133334444'],
        'emails' => ['contato@example.com'],
        ...$overrides,
    ];
}

function userAdmin(?School $school = null): User
{
    return User::factory()->admin()->create(['school_id' => ($school ?? School::factory()->create())->id]);
}

test('guests are redirected to the login page', function () {
    $this->get(route('user'))->assertRedirect(route('login'));
});

test('admins can list the people of their school with their access data', function () {
    $admin = userAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->create();
    $person->user->assignRole('teacher');
    Phone::factory()->create(['person_id' => $person->id]);

    $this->actingAs($admin)->get(route('user'))->assertInertia(fn ($page) => $page
        ->component('user/index')
        ->has('people', 1, fn ($row) => $row
            ->where('id', $person->id)
            ->where('user.roles.0.name', 'teacher')
            ->has('phones', 1)
            ->has('emails')
            ->etc()));
});

test('a user is registered together with the person, address, phones and emails', function () {
    $admin = userAdmin();

    $this->actingAs($admin)
        ->post(route('user.store'), userPayload())
        ->assertRedirect(route('user'));

    $user = User::where('email', 'ana@example.com')->sole();
    $person = Person::with(['address', 'phones', 'emails'])->where('user_id', $user->id)->sole();

    expect($user->school_id)->toBe($admin->school_id)
        ->and($user->roles)->toHaveCount(0)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->name)->toBe('Ana Prado')
        ->and($person->name)->toBe('Ana Prado')
        ->and($person->school_id)->toBe($admin->school_id)
        ->and($person->cpf_cnpj)->toBe('11122233396')
        ->and($person->address)->toMatchArray(['zip_code' => '01310100', 'state' => 'SP', 'number' => '1000'])
        ->and($person->phones->pluck('number')->all())->toBe(['11999990000', '1133334444'])
        ->and($person->emails->pluck('address')->all())->toBe(['contato@example.com']);
});

test('a user can be registered without address, phones and emails', function () {
    $this->actingAs(userAdmin())
        ->post(route('user.store'), userPayload(['address' => [], 'phones' => [], 'emails' => []]))
        ->assertRedirect(route('user'));

    $person = Person::with('address')->where('name', 'Ana Prado')->sole();

    expect($person->address)->toBeNull()->and($person->phones)->toHaveCount(0);
});

test('registering a user validates the payload', function () {
    $this->actingAs(userAdmin())
        ->post(route('user.store'), userPayload([
            'email' => 'invalid', 'cpf_cnpj' => '123', 'password' => 'short',
            'phones' => ['12'], 'emails' => ['not-an-email'],
        ]))
        ->assertSessionHasErrors(['email', 'cpf_cnpj', 'password', 'phones.0', 'emails.0']);
});

test('an address needs every required field once any is filled', function () {
    $this->actingAs(userAdmin())
        ->post(route('user.store'), userPayload(['address' => ['zip_code' => '01310100', 'street' => 'Avenida Paulista']]))
        ->assertSessionHasErrors(['address.neighborhood', 'address.city', 'address.state', 'address.number']);
});

test('the login e-mail is unique and the document is unique within the school only', function () {
    $admin = userAdmin();
    $existing = Person::factory()->inSchool($admin->school_id)->create(['cpf_cnpj' => '11122233396']);

    $this->actingAs($admin)
        ->post(route('user.store'), userPayload(['email' => $existing->user->email]))
        ->assertSessionHasErrors(['email', 'cpf_cnpj']);

    $this->actingAs(userAdmin())
        ->post(route('user.store'), userPayload())
        ->assertRedirect(route('user'));
});

test('a user and their person can be updated and contacts are replaced', function () {
    $admin = userAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->create();
    Phone::factory()->count(2)->create(['person_id' => $person->id]);
    Email::factory()->create(['person_id' => $person->id]);

    $this->actingAs($admin)
        ->put(route('user.update', $person), userPayload([
            'name' => 'Novo Nome', 'email' => 'novo@example.com', 'phones' => ['11988887777'], 'emails' => [],
        ]))
        ->assertRedirect(route('user'));

    $person = $person->fresh(['user', 'address', 'phones', 'emails']);

    expect($person->name)->toBe('Novo Nome')
        ->and($person->user->name)->toBe('Novo Nome')
        ->and($person->user->email)->toBe('novo@example.com')
        ->and($person->address->street)->toBe('Avenida Paulista')
        ->and($person->phones->pluck('number')->all())->toBe(['11988887777'])
        ->and($person->emails)->toHaveCount(0);
});

test('a school admin cannot see or change people from another school', function () {
    $admin = userAdmin();
    $foreign = Person::factory()->inSchool(School::factory()->create()->id)->create();

    $this->actingAs($admin)->put(route('user.update', $foreign), userPayload())->assertNotFound();

    $this->actingAs($admin)->get(route('user'))
        ->assertInertia(fn ($page) => $page->component('user/index')->has('people', 0));
});

test('users without the permission cannot manage users', function () {
    $user = User::factory()->withRole('teacher')->create();
    $this->actingAs($user);
    $person = Person::factory()->create();

    $this->get(route('user'))->assertForbidden();
    $this->post(route('user.store'), userPayload())->assertForbidden();
    $this->put(route('user.update', $person), userPayload())->assertForbidden();
});

test('a user granted users.manage can manage users', function () {
    $user = User::factory()->withRole('teacher')->create();
    $user->givePermissionTo('users.manage');

    $this->actingAs($user)->get(route('user'))->assertOk();
    $this->post(route('user.store'), userPayload())->assertRedirect(route('user'));
});

test('nothing is saved when registering a user fails midway', function () {
    Person::creating(fn () => throw new RuntimeException('boom'));

    $this->actingAs(userAdmin())
        ->post(route('user.store'), userPayload())
        ->assertRedirect(route('user'));

    $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    $this->assertDatabaseCount('people', 0);
    $this->assertDatabaseCount('addresses', 0);
});

test('a friendly error is shown when registering a user fails', function () {
    $this->mock(PersonRepository::class)
        ->shouldReceive('createWithUser')->andThrow(new RepositoryException('boom'));

    $this->actingAs(userAdmin())
        ->post(route('user.store'), userPayload())
        ->assertRedirect(route('user'));

    $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
});
