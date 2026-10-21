<?php

namespace Database\Seeders\Concerns;

use App\Models\Address;
use App\Models\Person;
use App\Models\School;
use App\Models\User;

trait CreatesSchoolPeople
{
    /**
     * Addresses in São Paulo, used in turn by the seeded people.
     *
     * @var list<array<string, string>>
     */
    private const ADDRESSES = [
        ['zip_code' => '01310100', 'street' => 'Avenida Paulista', 'neighborhood' => 'Bela Vista', 'number' => '1000'],
        ['zip_code' => '04538133', 'street' => 'Avenida Brigadeiro Faria Lima', 'neighborhood' => 'Itaim Bibi', 'number' => '3477'],
        ['zip_code' => '05424020', 'street' => 'Rua dos Pinheiros', 'neighborhood' => 'Pinheiros', 'number' => '275'],
        ['zip_code' => '04101300', 'street' => 'Rua Vergueiro', 'neighborhood' => 'Vila Mariana', 'number' => '2016'],
        ['zip_code' => '03310000', 'street' => 'Rua Tuiuti', 'neighborhood' => 'Tatuapé', 'number' => '1500'],
    ];

    /**
     * The school that receives the development data.
     */
    protected function defaultSchool(): School
    {
        return School::firstOrCreate(['code' => 'DEFAULT'], ['name' => 'Aprende Mais']);
    }

    protected function createUser(School $school, string $name, string $email, string $role, string $password = 'password'): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'email_verified_at' => now(),
            'school_id' => $school->id,
        ]);

        $user->syncRoles($role);

        return $user;
    }

    /**
     * Create the user's person, with a document, address, phone and e-mail derived from the given number.
     */
    protected function createPerson(User $user, ?int $number = null): Person
    {
        if ($number === null) {
            return Person::firstOrCreate(['user_id' => $user->id], [
                'school_id' => $user->school_id,
                'name' => $user->name,
            ]);
        }

        $person = Person::firstOrCreate(['user_id' => $user->id], [
            'school_id' => $user->school_id,
            'address_id' => Address::create([
                ...self::ADDRESSES[$number % count(self::ADDRESSES)],
                'city' => 'São Paulo',
                'state' => 'SP',
                'ibge_code' => '3550308',
                'ibge_state_code' => '35',
            ])->id,
            'name' => $user->name,
            'cpf_cnpj' => $this->cpf($number),
            'birth_date' => now()->subYears(18 + $number % 30)->subDays($number * 11)->toDateString(),
        ]);

        if ($person->wasRecentlyCreated) {
            $person->phones()->create(['number' => '119'.str_pad((string) (87650000 + $number), 8, '0', STR_PAD_LEFT)]);
            $person->emails()->create(['address' => $user->email]);
        }

        return $person;
    }

    /**
     * A valid CPF built from the given number, so each seeded person gets a stable document.
     */
    private function cpf(int $number): string
    {
        $digits = str_pad((string) (100000000 + $number * 7919), 9, '0', STR_PAD_LEFT);

        foreach ([10, 11] as $weight) {
            $sum = 0;

            foreach (str_split($digits) as $index => $digit) {
                $sum += (int) $digit * ($weight - $index);
            }

            $digits .= (string) ((($sum * 10) % 11) % 10);
        }

        return $digits;
    }
}
