<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Address;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class PersonRepository
{
    /**
     * Create a user with the given role together with its person, address, phones and emails.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWithUser(array $data, string $role): Person
    {
        try {
            return DB::transaction(function () use ($data, $role): Person {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'school_id' => auth()->user()->school_id,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $user->assignRole($role);

                $person = Person::query()->create([
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'name' => $data['name'],
                    'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                ]);

                $this->syncAddress($person, $data['address'] ?? []);
                $this->syncContacts($person, $data);

                return $person;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create person.', ['email' => $data['email'] ?? null], $e);
        }
    }

    /**
     * Update a person, the name and e-mail of their user, and their address, phones and emails.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Person $person, array $data): Person
    {
        try {
            return DB::transaction(function () use ($person, $data): Person {
                $person->user->update(['name' => $data['name'], 'email' => $data['email']]);
                $person->update([
                    'name' => $data['name'],
                    'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                ]);

                $this->syncAddress($person, $data['address'] ?? []);
                $this->syncContacts($person, $data);

                return $person;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update person.', ['person_id' => $person->id], $e);
        }
    }

    /**
     * Create, update or remove the person's single address depending on whether any field is filled.
     *
     * @param  array<string, mixed>  $address
     */
    private function syncAddress(Person $person, array $address): void
    {
        $filled = array_filter($address, fn ($value) => filled($value));

        if ($filled === []) {
            if ($person->address_id !== null) {
                $person->address?->delete();
                $person->update(['address_id' => null]);
            }

            return;
        }

        $attributes = ['complement' => null, ...$filled];

        if ($person->address !== null) {
            $person->address->update($attributes);

            return;
        }

        $person->update(['address_id' => Address::query()->create($attributes)->id]);
    }

    /**
     * Replace the person's phones and emails with the given lists.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncContacts(Person $person, array $data): void
    {
        $person->phones()->forceDelete();
        $person->phones()->createMany(array_map(fn (string $number) => ['number' => $number], $data['phones'] ?? []));

        $person->emails()->forceDelete();
        $person->emails()->createMany(array_map(fn (string $address) => ['address' => $address], $data['emails'] ?? []));
    }
}
