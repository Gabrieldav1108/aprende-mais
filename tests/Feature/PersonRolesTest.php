<?php

use App\Models\Classroom;
use App\Models\Person;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

const LINK_MIGRATION = 'database/migrations/2026_10_08_213254_link_students_and_teachers_to_people.php';

test('students and teachers expose their person and user', function () {
    $student = Student::factory()->create();
    $teacher = Teacher::factory()->create();

    expect($student->person)->toBeInstanceOf(Person::class)
        ->and($teacher->person->user->teacher->is($teacher))->toBeTrue()
        ->and($student->person->user->student->is($student))->toBeTrue()
        ->and($student->person->student->is($student))->toBeTrue();
});

test('one person can be both a student and a teacher', function () {
    $person = Person::factory()->create();
    Student::factory()->create(['person_id' => $person->id]);
    Teacher::factory()->create(['person_id' => $person->id]);

    expect($person->student)->not->toBeNull()
        ->and($person->teacher)->not->toBeNull()
        ->and(Person::count())->toBe(1);
});

test('students and teachers are scoped to the school of the authenticated user', function () {
    $school = School::factory()->create();
    $ownStudent = Student::factory()->inSchool($school->id)->create();
    $ownTeacher = Teacher::factory()->inSchool($school->id)->create();
    Student::factory()->create();
    Teacher::factory()->create();

    $this->actingAs($ownStudent->person->user);

    expect(Student::sole()->is($ownStudent))->toBeTrue()
        ->and(Teacher::sole()->is($ownTeacher))->toBeTrue();
});

test('users without a school see no students or teachers', function () {
    Student::factory()->create();
    Teacher::factory()->create();

    $this->actingAs(User::factory()->create(['school_id' => null]));

    expect(Student::count())->toBe(0)->and(Teacher::count())->toBe(0);
});

test('guests are not restricted when querying students', function () {
    Student::factory()->count(2)->create();

    expect(Student::count())->toBe(2);
});

test('updating the profile keeps the person name in sync', function () {
    $person = Person::factory()->create();

    $this->actingAs($person->user)
        ->patch(route('profile.update'), ['name' => 'Novo Nome', 'email' => $person->user->email])
        ->assertRedirect(route('profile.edit'));

    expect($person->fresh()->name)->toBe('Novo Nome')
        ->and($person->user->fresh()->name)->toBe('Novo Nome');
});

test('the migration turns existing students and teachers into people', function () {
    $school = School::factory()->create();
    $studentAndTeacher = User::factory()->create(['school_id' => $school->id, 'name' => 'Duplo Papel']);
    $studentOnly = User::factory()->create(['school_id' => null, 'name' => 'Sem Escola']);

    Artisan::call('migrate:rollback', ['--path' => LINK_MIGRATION, '--force' => true]);

    $now = now();
    DB::table('students')->insert([
        ['school_id' => $school->id, 'user_id' => $studentAndTeacher->id, 'enrollment_number' => 'A1', 'created_at' => $now, 'updated_at' => $now],
        ['school_id' => null, 'user_id' => $studentOnly->id, 'enrollment_number' => 'A2', 'created_at' => $now, 'updated_at' => $now],
    ]);
    DB::table('teachers')->insert([
        'school_id' => $school->id, 'user_id' => $studentAndTeacher->id, 'registration_number' => 'T1', 'created_at' => $now, 'updated_at' => $now,
    ]);

    Artisan::call('migrate', ['--path' => LINK_MIGRATION, '--force' => true]);

    $both = Person::withoutGlobalScopes()->where('user_id', $studentAndTeacher->id)->sole();
    $fallback = Person::withoutGlobalScopes()->where('user_id', $studentOnly->id)->sole();

    expect(Person::withoutGlobalScopes()->count())->toBe(2)
        ->and($both->name)->toBe('Duplo Papel')
        ->and($both->school_id)->toBe($school->id)
        ->and(Student::withoutGlobalScopes()->where('person_id', $both->id)->exists())->toBeTrue()
        ->and(Teacher::withoutGlobalScopes()->where('person_id', $both->id)->exists())->toBeTrue()
        ->and($fallback->school_id)->toBe($school->id)
        ->and(Schema::hasColumns('students', ['school_id', 'user_id']))->toBeFalse()
        ->and(Schema::hasColumns('teachers', ['school_id', 'user_id']))->toBeFalse();
});

test('a subject cannot be offered with a teacher from another school', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create(['course_id' => $classroom->course_id]);

    $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $subject->id,
        'teacher_id' => Teacher::factory()->create()->id,
    ])->assertRedirect();

    $foreign = Teacher::factory()->inSchool(School::factory()->create()->id)->create();

    $this->post(route('classroom.subjects.store', $classroom), [
        'subject_id' => $subject->id,
        'teacher_id' => $foreign->id,
    ])->assertSessionHasErrors('teacher_id');
});
