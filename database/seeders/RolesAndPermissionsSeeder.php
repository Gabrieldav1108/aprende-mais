<?php

namespace Database\Seeders;

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
        'classrooms.create',
        'classrooms.update',
        'classrooms.delete',
        'subjects.create',
        'subjects.delete',
        'users.manage',
    ];

    /**
     * @var list<string>
     */
    public const ROLES = ['admin', 'teacher', 'student'];

    /**
     * Seed the roles and permissions, and optionally bootstrap an admin user.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
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

        $admin->assignRole('admin');
    }
}
