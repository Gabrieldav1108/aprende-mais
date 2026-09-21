<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DefaultUsersSeeder;

test('the seeder creates an admin, a teacher and a student for each person', function (string $person) {
    $this->seed(DefaultUsersSeeder::class);

    $admin = User::where('email', "{$person}.admin@aprendemais.com")->sole();
    $teacher = User::where('email', "{$person}.professor@aprendemais.com")->sole();
    $student = User::where('email', "{$person}.aluno@aprendemais.com")->sole();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->teacher)->toBeInstanceOf(Teacher::class)
        ->and($student->hasRole('student'))->toBeTrue()
        ->and($student->student)->toBeInstanceOf(Student::class);
})->with(['gabriel', 'arthur']);

test('the seeder can run twice without duplicating records', function () {
    $this->seed(DefaultUsersSeeder::class);
    $countAfterFirstRun = User::count();

    $this->seed(DefaultUsersSeeder::class);

    expect(User::count())->toBe($countAfterFirstRun)
        ->and(Teacher::count())->toBe(User::role('teacher')->count())
        ->and(Student::count())->toBe(User::role('student')->count());
});

test('the seeded users can log in with the default password', function () {
    $this->seed(DefaultUsersSeeder::class);

    $this->post(route('login.store'), [
        'email' => 'gabriel.admin@aprendemais.com',
        'password' => DefaultUsersSeeder::PASSWORD,
    ]);

    $this->assertAuthenticated();
});
