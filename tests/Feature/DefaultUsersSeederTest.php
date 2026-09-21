<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DefaultUsersSeeder;

test('the seeder creates an admin, a teacher and a student', function () {
    $this->seed(DefaultUsersSeeder::class);

    $admin = User::where('email', 'gabriel.regra.admin@aprendemais.com')->sole();
    $teacher = User::where('email', 'gabriel.regra.professor@aprendemais.com')->sole();
    $student = User::where('email', 'gabriel.regra.aluno@aprendemais.com')->sole();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->teacher)->toBeInstanceOf(Teacher::class)
        ->and($student->hasRole('student'))->toBeTrue()
        ->and($student->student)->toBeInstanceOf(Student::class);
});

test('the seeder can run twice without duplicating records', function () {
    $this->seed(DefaultUsersSeeder::class);
    $this->seed(DefaultUsersSeeder::class);

    expect(User::count())->toBe(3)
        ->and(Teacher::count())->toBe(1)
        ->and(Student::count())->toBe(1);
});

test('the seeded users can log in with the default password', function () {
    $this->seed(DefaultUsersSeeder::class);

    $this->post(route('login.store'), [
        'email' => 'gabriel.regra.admin@aprendemais.com',
        'password' => DefaultUsersSeeder::PASSWORD,
    ]);

    $this->assertAuthenticated();
});
