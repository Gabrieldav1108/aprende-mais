<?php

namespace Database\Seeders;

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

        $teacher = $this->createUser($school, 'Gabriel (Professor)', 'gabriel.professor@aprendemais.com', 'teacher');
        Teacher::firstOrCreate(['user_id' => $teacher->id], [
            'school_id' => $school->id,
            'registration_number' => 'T00001',
            'qualification' => 'Licenciatura',
        ]);

        $student = $this->createUser($school, 'Gabriel (Aluno)', 'gabriel.aluno@aprendemais.com', 'student');
        Student::firstOrCreate(['user_id' => $student->id], [
            'school_id' => $school->id,
            'enrollment_number' => 'A00001',
        ]);

        $this->createUser($school, 'Arthur (Admin)', 'arthur.admin@aprendemais.com', 'admin');

        $teacher = $this->createUser($school, 'Arthur (Professor)', 'arthur.professor@aprendemais.com', 'teacher');
        Teacher::firstOrCreate(['user_id' => $teacher->id], [
            'school_id' => $school->id,
            'registration_number' => 'T00002',
            'qualification' => 'Licenciatura',
        ]);

        $student = $this->createUser($school, 'Arthur (Aluno)', 'arthur.aluno@aprendemais.com', 'student');
        Student::firstOrCreate(['user_id' => $student->id], [
            'school_id' => $school->id,
            'enrollment_number' => 'A00002',
        ]);
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
}
