<?php

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function subjectPayload(array $overrides = []): array
{
    return [
        'name' => 'Cálculo I',
        'code' => 'MAT-101',
        'workload_hours' => 80,
        'teacher_id' => Teacher::factory()->create()->id,
        ...$overrides,
    ];
}

test('the subjects screen lists the catalog', function () {
    $this->actingAs(User::factory()->admin()->create());
    Subject::factory()->count(2)->create();

    $this->get(route('subject'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('subjects/index')
            ->has('subjects', 2)
            ->has('teachers')
            ->has('knowledgeAreas'));
});

test('a subject is registered with its teacher', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('subject.store'), subjectPayload())
        ->assertRedirect(route('subject'));

    $subject = Subject::where('code', 'MAT-101')->sole();

    expect($subject->teacher_id)->not->toBeNull()
        ->and($subject->courses)->toBeEmpty();
});

test('a subject requires a teacher and a unique code', function () {
    $this->actingAs(User::factory()->admin()->create());
    Subject::factory()->create(['code' => 'MAT-101']);

    $this->post(route('subject.store'), subjectPayload(['teacher_id' => null]))
        ->assertSessionHasErrors(['teacher_id', 'code']);
});

test('a subject can be edited', function () {
    $this->actingAs(User::factory()->admin()->create());
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();

    $this->put(route('subject.update', $subject), subjectPayload([
        'name' => 'Novo nome',
        'code' => $subject->code,
        'teacher_id' => $teacher->id,
    ]))->assertRedirect(route('subject'));

    expect($subject->refresh())
        ->name->toBe('Novo nome')
        ->teacher_id->toBe($teacher->id);
});

test('users without permission cannot use the subjects screen', function () {
    $student = User::factory()->withRole('student')->create();
    $this->actingAs($student);
    $subject = Subject::factory()->create();

    $this->actingAs($student)->get(route('subject'))->assertForbidden();
    $this->actingAs($student)->post(route('subject.store'), subjectPayload())->assertForbidden();
    $this->actingAs($student)->put(route('subject.update', $subject), subjectPayload())->assertForbidden();
});
