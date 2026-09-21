<?php

namespace Database\Seeders;

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

        $this->createUser('Gabriel (Admin)', 'gabriel.admin@aprendemais.com', 'admin');

        $teacher = $this->createUser('Gabriel (Professor)', 'gabriel.professor@aprendemais.com', 'teacher');
        Teacher::firstOrCreate(['user_id' => $teacher->id], [
            'registration_number' => 'T00001',
            'qualification' => 'Licenciatura',
        ]);

        $student = $this->createUser('Gabriel (Aluno)', 'gabriel.aluno@aprendemais.com', 'student');
        Student::firstOrCreate(['user_id' => $student->id], [
            'enrollment_number' => 'A00001',
        ]);
    }

    private function createUser(string $name, string $email, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($role);

        return $user;
    }
}
