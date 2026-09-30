<?php

use App\Models\AcademicTerm;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function termPayload(array $overrides = []): array
{
    return [
        'year' => 2026,
        'number' => 1,
        'starts_at' => '2026-02-01',
        'ends_at' => '2026-06-30',
        'status' => 'planned',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('academic-term'))->assertRedirect(route('login'));
});

test('authenticated users can list academic terms', function () {
    $this->actingAs(User::factory()->create());
    AcademicTerm::factory()->count(2)->create();

    $this->get(route('academic-term'))->assertOk();
});

test('an academic term is created with a generated name', function () {
    $school = School::factory()->create();
    $admin = User::factory()->admin()->create(['school_id' => $school->id]);

    $this->actingAs($admin)
        ->post(route('academic-term.store'), termPayload())
        ->assertRedirect(route('academic-term'));

    $this->assertDatabaseHas('academic_terms', [
        'school_id' => $school->id,
        'name' => '2026/1',
        'year' => 2026,
        'number' => 1,
        'status' => 'planned',
    ]);
});

test('a term number is unique per year within the school', function () {
    $school = School::factory()->create();
    $admin = User::factory()->admin()->create(['school_id' => $school->id]);
    AcademicTerm::factory()->create(['school_id' => $school->id, 'year' => 2026, 'number' => 1]);

    $this->actingAs($admin)
        ->post(route('academic-term.store'), termPayload())
        ->assertSessionHasErrors('number');
});

test('a term must end after it starts and only allows two semesters', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('academic-term.store'), termPayload(['number' => 3, 'ends_at' => '2026-01-01']))
        ->assertSessionHasErrors(['number', 'ends_at']);
});

test('an academic term can be closed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $term = AcademicTerm::factory()->create(['year' => 2026, 'number' => 1, 'status' => 'open']);

    $this->put(route('academic-term.update', $term), termPayload(['status' => 'closed']))
        ->assertRedirect(route('academic-term'));

    $this->assertDatabaseHas('academic_terms', ['id' => $term->id, 'status' => 'closed']);
});

test('non-admin users cannot manage academic terms', function () {
    $term = AcademicTerm::factory()->create();
    $teacher = User::factory()->withRole('teacher')->create();

    $this->actingAs($teacher)->post(route('academic-term.store'), termPayload())->assertForbidden();
    $this->actingAs($teacher)->put(route('academic-term.update', $term), termPayload())->assertForbidden();
});
