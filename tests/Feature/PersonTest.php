<?php

use App\Models\Address;
use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a user has one person with an address, phones and emails', function () {
    $person = Person::factory()->for(Address::factory())->create();
    Phone::factory()->count(2)->create(['person_id' => $person->id]);
    Email::factory()->create(['person_id' => $person->id]);

    $loaded = $person->user->person()->with(['address', 'phones', 'emails'])->sole();

    expect($loaded->is($person))->toBeTrue()
        ->and($loaded->address)->toBeInstanceOf(Address::class)
        ->and($loaded->phones)->toHaveCount(2)
        ->and($loaded->emails)->toHaveCount(1)
        ->and($loaded->birth_date->format('Y-m-d'))->toBe($person->birth_date->format('Y-m-d'));
});

test('a user can have only one person', function () {
    $person = Person::factory()->create();

    expect(fn () => Person::factory()->create(['user_id' => $person->user_id]))
        ->toThrow(QueryException::class);
});

test('people are scoped to the school of the authenticated user', function () {
    $school = School::factory()->create();
    $own = Person::factory()->inSchool($school->id)->create();
    Person::factory()->create();

    $this->actingAs($own->user);

    expect(Person::count())->toBe(1)
        ->and(Person::sole()->is($own))->toBeTrue();
});

test('users without a school see no people', function () {
    Person::factory()->create();

    $this->actingAs(User::factory()->create(['school_id' => null]));

    expect(Person::count())->toBe(0);
});

test('a person created by a school user receives that school', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    $this->actingAs($user);

    $person = Person::query()->create(['user_id' => $user->id, 'name' => $user->name]);

    expect($person->school_id)->toBe($school->id);
});

test('deleting a person removes its phones and emails from the database', function () {
    $person = Person::factory()->create();
    Phone::factory()->create(['person_id' => $person->id]);

    $person->forceDelete();

    expect(Phone::withTrashed()->count())->toBe(0);
});
