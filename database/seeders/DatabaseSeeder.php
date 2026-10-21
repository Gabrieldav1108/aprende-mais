<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::firstOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'password' => 'password',
            'email_verified_at' => now(),
        ])->syncRoles(UserRole::Student->value);

        if (! app()->isProduction()) {
            $this->call([
                DefaultUsersSeeder::class,
                KnowledgeAreaSeeder::class,
                CourseSeeder::class,
                TeacherSeeder::class,
                SubjectSeeder::class,
                AcademicTermSeeder::class,
                StudentSeeder::class,
                ClassroomSeeder::class,
            ]);
        }
    }
}
