<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Address;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PersonRepository
{
    /**
     * List the people of the school with their user (login e-mail and roles), address and contacts.
     *
     * @return Collection<int, Person>
     */
    public function all(): Collection
    {
        try {
            return Person::query()
                ->with(['user:id,email,email_verified_at', 'user.roles:id,name', 'address', 'phones', 'emails'])
                ->get()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list people.', previous: $e);
        }
    }

    /**
     * List the people of the school whose user has the given role (student or teacher) and that are not linked to it yet.
     *
     * @return Collection<int, Person>
     */
    public function availableFor(string $role): Collection
    {
        try {
            return Person::query()
                ->whereHas('user', fn ($query) => $query->role($role))
                ->whereDoesntHave($role)
                ->orderBy('name')
                ->get(['id', 'name']);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list people available for role.', ['role' => $role], $e);
        }
    }

    /**
     * Create a user with its roles together with its person, address, phones and emails.
     * The user gets a random password and an unverified e-mail until they accept the invitation.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWithUser(array $data): Person
    {
        try {
            return DB::transaction(function () use ($data): Person {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Str::password(32),
                    'school_id' => auth()->user()->school_id,
                ]);
                $user->syncRoles($data['roles'] ?? []);

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
     * Update a person, the name, e-mail and roles of their user, and their address, phones and emails.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Person $person, array $data): Person
    {
        try {
            return DB::transaction(function () use ($person, $data): Person {
                $person->user->update(['name' => $data['name'], 'email' => $data['email']]);
                $person->user->syncRoles($data['roles'] ?? []);
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

        $attributes = ['complement' => null, 'ibge_code' => null, 'ibge_state_code' => null, ...$filled];

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
