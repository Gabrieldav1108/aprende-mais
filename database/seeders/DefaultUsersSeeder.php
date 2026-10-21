<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class DefaultUsersSeeder extends Seeder
{
    use CreatesSchoolPeople;

    public const PASSWORD = 'password';

    /**
     * Seed one admin, one teacher and one student for local development.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $school = $this->defaultSchool();

        $this->createUser($school, 'Gabriel (Admin)', 'gabriel.admin@aprendemais.com', UserRole::Admin->value, self::PASSWORD);

        $person = $this->createPerson($this->createUser($school, 'Gabriel (Professor)', 'gabriel.professor@aprendemais.com', UserRole::Teacher->value, self::PASSWORD));
        Teacher::firstOrCreate(['person_id' => $person->id], [
            'registration_number' => 'T00001',
            'qualification' => 'Licenciatura',
        ]);

        $person = $this->createPerson($this->createUser($school, 'Gabriel (Aluno)', 'gabriel.aluno@aprendemais.com', UserRole::Student->value, self::PASSWORD));
        Student::firstOrCreate(['person_id' => $person->id], ['enrollment_number' => 'A00001']);

        $this->createUser($school, 'Arthur (Admin)', 'arthur.admin@aprendemais.com', UserRole::Admin->value, self::PASSWORD);

        $person = $this->createPerson($this->createUser($school, 'Arthur (Professor)', 'arthur.professor@aprendemais.com', UserRole::Teacher->value, self::PASSWORD));
        Teacher::firstOrCreate(['person_id' => $person->id], [
            'registration_number' => 'T00002',
            'qualification' => 'Licenciatura',
        ]);

        $person = $this->createPerson($this->createUser($school, 'Arthur (Aluno)', 'arthur.aluno@aprendemais.com', UserRole::Student->value, self::PASSWORD));
        Student::firstOrCreate(['person_id' => $person->id], ['enrollment_number' => 'A00002']);
    }
}
