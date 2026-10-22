<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const PERMISSIONS = [
        'classrooms.view',
        'classrooms.view-all',
        'classrooms.create',
        'classrooms.update',
        'classrooms.delete',
        'subjects.view',
        'subjects.view-all',
        'subjects.view-enrolled',
        'subjects.create',
        'subjects.update',
        'subjects.delete',
        'courses.view',
        'courses.create',
        'courses.update',
        'courses.delete',
        'terms.view',
        'terms.create',
        'terms.update',
        'students.view',
        'students.view-all',
        'students.create',
        'students.update',
        'teachers.view',
        'teachers.create',
        'teachers.update',
        'users.manage',
    ];

    /**
     * Permissions granted to each non-admin role. Admins bypass every check through Gate::before.
     *
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        'teacher' => [
            'classrooms.view',
            'subjects.view',
            'students.view',
        ],
        'student' => [
            'subjects.view-enrolled',
        ],
    ];

    /**
     * Seed the roles and permissions, and optionally bootstrap an admin user.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions(self::ROLE_PERMISSIONS[$role->value] ?? []);
        }

        $this->bootstrapAdmin();
    }

    /**
     * Create the admin user configured through ADMIN_EMAIL and ADMIN_PASSWORD, if any.
     */
    private function bootstrapAdmin(): void
    {
        $email = config('services.admin_bootstrap.email');
        $password = config('services.admin_bootstrap.password');

        if (! $email || ! $password) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('services.admin_bootstrap.name'),
                'password' => $password,
            ],
        );

        if ($admin->email_verified_at === null) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $admin->assignRole(UserRole::Admin->value);
    }
}
