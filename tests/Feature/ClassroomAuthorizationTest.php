<?php

use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function classroomWithSubject(): array
{
    $classroom = Classroom::factory()->create();
    $classroomSubject = ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->create()->id,
        'teacher_id' => Teacher::factory()->create()->id,
    ]);

    return [$classroom, $classroomSubject];
}

dataset('non-admin users', [
    'without a role' => fn () => User::factory()->create(),
    'student' => fn () => User::factory()->withRole('student')->create(),
    'teacher' => fn () => User::factory()->withRole('teacher')->create(),
]);

test('non-admin users cannot create classrooms', function (User $user) {
    $this->actingAs($user)
        ->post(route('classroom.store'), ['name' => 'Turma A', 'school_year' => '2026', 'shift' => 'morning'])
        ->assertForbidden();

    $this->assertDatabaseMissing('classrooms', ['name' => 'Turma A']);
})->with('non-admin users');

test('non-admin users cannot update classrooms', function (User $user) {
    $classroom = Classroom::factory()->create(['name' => 'Turma A']);

    $this->actingAs($user)
        ->put(route('classroom.update', $classroom), ['name' => 'Turma B', 'school_year' => '2027', 'shift' => 'evening'])
        ->assertForbidden();

    $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'name' => 'Turma A']);
})->with('non-admin users');

test('non-admin users cannot add subjects to a classroom', function (User $user) {
    $classroom = Classroom::factory()->create();
    $teacher = Teacher::factory()->create();

    $this->actingAs($user)
        ->post(route('classroom.subjects.store', $classroom), [
            'name' => 'Matemática',
            'code' => 'MAT-101',
            'workload_hours' => 80,
            'teacher_id' => $teacher->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('subjects', ['code' => 'MAT-101']);
})->with('non-admin users');

test('non-admin users cannot remove subjects from a classroom', function (User $user) {
    [$classroom, $classroomSubject] = classroomWithSubject();

    $this->actingAs($user)
        ->delete(route('classroom.subjects.destroy', [$classroom, $classroomSubject]))
        ->assertForbidden();

    $this->assertNotSoftDeleted($classroomSubject);
})->with('non-admin users');

test('non-admin users can still view classrooms', function (User $user) {
    $classroom = Classroom::factory()->create();

    $this->actingAs($user)->get(route('classroom'))->assertOk();
    $this->actingAs($user)->get(route('classroom.show', $classroom))->assertOk();
})->with('non-admin users');

test('users granted a permission can perform the matching action', function () {
    $teacher = User::factory()->withRole('teacher')->create();
    $teacher->givePermissionTo('classrooms.create');

    $this->actingAs($teacher)
        ->post(route('classroom.store'), ['name' => 'Turma A', 'school_year' => '2026', 'shift' => 'morning'])
        ->assertRedirect(route('classroom'));

    $classroom = Classroom::factory()->create();

    $this->actingAs($teacher)
        ->put(route('classroom.update', $classroom), ['name' => 'Turma B', 'school_year' => '2027', 'shift' => 'evening'])
        ->assertForbidden();
});

test('admins can perform every classroom and subject action', function () {
    $admin = User::factory()->admin()->create();
    [$classroom, $classroomSubject] = classroomWithSubject();
    $teacher = Teacher::factory()->create();

    $this->actingAs($admin)
        ->post(route('classroom.store'), ['name' => 'Turma A', 'school_year' => '2026', 'shift' => 'morning'])
        ->assertRedirect(route('classroom'));

    $this->actingAs($admin)
        ->put(route('classroom.update', $classroom), ['name' => 'Turma B', 'school_year' => '2027', 'shift' => 'evening'])
        ->assertRedirect(route('classroom.show', $classroom));

    $this->actingAs($admin)
        ->post(route('classroom.subjects.store', $classroom), [
            'name' => 'Matemática',
            'code' => 'MAT-101',
            'workload_hours' => 80,
            'teacher_id' => $teacher->id,
        ])
        ->assertRedirect(route('classroom.show', $classroom));

    $this->actingAs($admin)
        ->delete(route('classroom.subjects.destroy', [$classroom, $classroomSubject]))
        ->assertRedirect(route('classroom.show', $classroom));

    $this->assertSoftDeleted($classroomSubject);
});

test('roles and permissions are shared with the frontend', function () {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->withRole('teacher')->create();
    $teacher->givePermissionTo('classrooms.create');

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.roles', ['admin']));

    $this->actingAs($teacher)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.roles', ['teacher'])
            ->where('auth.permissions', ['classrooms.create']));
});
