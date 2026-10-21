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
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->create()->id,
    ]);

    return [$classroom, $classroomSubject];
}

function classroomUpdatePayload(Classroom $classroom, array $overrides = []): array
{
    return [
        'academic_term_id' => $classroom->academic_term_id,
        'name' => 'Turma B',
        'code' => 'B-2027',
        'term_number' => 2,
        'shift' => 'evening',
        'status' => 'active',
        'capacity' => 30,
        ...$overrides,
    ];
}

dataset('non-admin users', [
    'without a role' => fn () => User::factory()->create(),
    'student' => fn () => User::factory()->withRole('student')->create(),
    'teacher' => fn () => User::factory()->withRole('teacher')->create(),
]);

test('non-admin users cannot create classrooms', function (User $user) {
    $this->actingAs($user)
        ->post(route('classroom.store'), classroomPayload())
        ->assertForbidden();

    $this->assertDatabaseMissing('classrooms', ['name' => 'Turma A']);
})->with('non-admin users');

test('non-admin users cannot update classrooms', function (User $user) {
    $this->actingAs($user);
    $classroom = Classroom::factory()->create(['name' => 'Turma A']);

    $this->actingAs($user)
        ->put(route('classroom.update', $classroom), classroomUpdatePayload($classroom))
        ->assertForbidden();

    $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'name' => 'Turma A']);
})->with('non-admin users');

test('non-admin users cannot add subjects to a classroom', function (User $user) {
    $this->actingAs($user);
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->forCourse($classroom->course_id)->create();
    $teacher = Teacher::factory()->create();

    $this->actingAs($user)
        ->post(route('classroom.subjects.store', $classroom), [
            'subject_id' => $subject->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('classroom_subjects', 0);
})->with('non-admin users');

test('non-admin users cannot remove subjects from a classroom', function (User $user) {
    $this->actingAs($user);
    [$classroom, $classroomSubject] = classroomWithSubject();

    $this->actingAs($user)
        ->delete(route('classroom.subjects.destroy', [$classroom, $classroomSubject]))
        ->assertForbidden();

    $this->assertNotSoftDeleted($classroomSubject);
})->with('non-admin users');

test('teachers can view the classrooms they teach', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher->person->user);
    $classroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
    $teacher = $teacher->person->user;

    $this->actingAs($teacher)->get(route('classroom'))->assertOk();
    $this->actingAs($teacher)->get(route('classroom.show', $classroom))->assertOk();
});

test('students and users without a role cannot view classrooms', function (User $user) {
    $this->actingAs($user);
    $classroom = Classroom::factory()->create();

    $this->actingAs($user)->get(route('classroom'))->assertForbidden();
    $this->actingAs($user)->get(route('classroom.show', $classroom))->assertForbidden();
})->with([
    'without a role' => fn () => User::factory()->create(),
    'student' => fn () => User::factory()->withRole('student')->create(),
]);

test('users granted a permission can perform the matching action', function () {
    $teacher = User::factory()->withRole('teacher')->create();
    $teacher->givePermissionTo('classrooms.create');

    $this->actingAs($teacher)
        ->post(route('classroom.store'), classroomPayload())
        ->assertRedirect(route('classroom'));

    $classroom = Classroom::factory()->create();

    $this->actingAs($teacher)
        ->put(route('classroom.update', $classroom), classroomUpdatePayload($classroom))
        ->assertForbidden();
});

test('admins can perform every classroom and subject action', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    [$classroom, $classroomSubject] = classroomWithSubject();
    $newSubject = Subject::factory()->forCourse($classroom->course_id)->create();
    $teacher = Teacher::factory()->create();

    $this->actingAs($admin)
        ->post(route('classroom.store'), classroomPayload())
        ->assertRedirect(route('classroom'));

    $this->actingAs($admin)
        ->from(route('classroom.show', $classroom))
        ->put(route('classroom.update', $classroom), classroomUpdatePayload($classroom))
        ->assertRedirect(route('classroom.show', $classroom));

    $this->actingAs($admin)
        ->post(route('classroom.subjects.store', $classroom), [
            'subject_id' => $newSubject->id,
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
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->sort()->values()->all() === [
                'classrooms.create',
                'classrooms.view',
                'students.view',
                'subjects.view',
            ]));
});
