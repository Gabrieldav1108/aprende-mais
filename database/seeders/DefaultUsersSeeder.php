<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultUsersSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /**
     * Seed one admin, one teacher and one student for local development.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $school = School::firstOrCreate(['code' => 'DEFAULT'], ['name' => 'Aprende Mais']);

        $this->createUser($school, 'Gabriel (Admin)', 'gabriel.admin@aprendemais.com', 'admin');

        $person = $this->createPerson($this->createUser($school, 'Gabriel (Professor)', 'gabriel.professor@aprendemais.com', 'teacher'));
        Teacher::firstOrCreate(['person_id' => $person->id], [
            'registration_number' => 'T00001',
            'qualification' => 'Licenciatura',
        ]);

        $person = $this->createPerson($this->createUser($school, 'Gabriel (Aluno)', 'gabriel.aluno@aprendemais.com', 'student'));
        Student::firstOrCreate(['person_id' => $person->id], ['enrollment_number' => 'A00001']);

        $this->createUser($school, 'Arthur (Admin)', 'arthur.admin@aprendemais.com', 'admin');

        $person = $this->createPerson($this->createUser($school, 'Arthur (Professor)', 'arthur.professor@aprendemais.com', 'teacher'));
        Teacher::firstOrCreate(['person_id' => $person->id], [
            'registration_number' => 'T00002',
            'qualification' => 'Licenciatura',
        ]);

        $person = $this->createPerson($this->createUser($school, 'Arthur (Aluno)', 'arthur.aluno@aprendemais.com', 'student'));
        Student::firstOrCreate(['person_id' => $person->id], ['enrollment_number' => 'A00002']);
    }

    private function createUser(School $school, string $name, string $email, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
            'school_id' => $school->id,
        ]);

        $user->syncRoles($role);

        return $user;
    }

    private function createPerson(User $user): Person
    {
        return Person::firstOrCreate(['user_id' => $user->id], [
            'school_id' => $user->school_id,
            'name' => $user->name,
        ]);
    }
}
