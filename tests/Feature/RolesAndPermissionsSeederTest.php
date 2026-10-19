<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the seeder creates the roles and permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::pluck('name')->all())->toEqualCanonicalizing(['admin', 'teacher', 'student'])
        ->and(Permission::pluck('name')->all())->toEqualCanonicalizing([
            'classrooms.create',
            'classrooms.update',
            'classrooms.delete',
            'subjects.view',
            'subjects.create',
            'subjects.update',
            'subjects.delete',
            'courses.create',
            'courses.update',
            'courses.delete',
            'terms.create',
            'terms.update',
            'students.view',
            'students.create',
            'students.update',
            'teachers.view',
            'teachers.create',
            'teachers.update',
            'users.manage',
        ]);
});

test('the seeder can run twice without duplicating records', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe(3)
        ->and(Permission::count())->toBe(19);
});

test('the seeder bootstraps an admin when credentials are configured', function () {
    config([
        'services.admin_bootstrap.email' => 'admin@example.com',
        'services.admin_bootstrap.password' => 'secret-password',
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::where('email', 'admin@example.com')->sole();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

test('the seeder does not create an admin without credentials', function () {
    config([
        'services.admin_bootstrap.email' => null,
        'services.admin_bootstrap.password' => null,
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(User::count())->toBe(0);
});

test('public registration assigns the student role', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->seed(RolesAndPermissionsSeeder::class);

    $this->post(route('register.store'), [
        'name' => 'Novo Aluno',
        'email' => 'aluno@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'aluno@example.com')->sole()->hasRole('student'))->toBeTrue();
});
