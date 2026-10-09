<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\KnowledgeArea;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CATALOG_MIGRATION = 'database/migrations/2026_10_08_232844_create_knowledge_areas_and_course_subject.php';

test('a new school starts with the default knowledge areas', function () {
    $school = School::factory()->create();
    School::factory()->create();

    $areas = KnowledgeArea::withoutGlobalScopes()->where('school_id', $school->id)->get();

    expect($areas)->toHaveCount(count(KnowledgeArea::DEFAULTS))
        ->and($areas->pluck('name')->all())->toContain('Ciências Exatas e da Terra', 'Ciências Humanas')
        ->and(KnowledgeArea::withoutGlobalScopes()->count())->toBe(2 * count(KnowledgeArea::DEFAULTS));
});

test('knowledge areas are scoped to the school of the authenticated user', function () {
    $school = School::factory()->create();
    School::factory()->create();

    $this->actingAs(User::factory()->create(['school_id' => $school->id]));

    expect(KnowledgeArea::count())->toBe(count(KnowledgeArea::DEFAULTS))
        ->and(KnowledgeArea::pluck('school_id')->unique()->all())->toBe([$school->id]);

    $this->actingAs(User::factory()->create(['school_id' => null]));

    expect(KnowledgeArea::count())->toBe(0);
});

test('a subject can belong to several courses with a suggested term in each', function () {
    $school = School::factory()->create();
    $first = Course::factory()->create(['school_id' => $school->id]);
    $second = Course::factory()->create(['school_id' => $school->id]);
    $subject = Subject::factory()->forCourse($first, 1)->create();
    $subject->courses()->attach($second, ['suggested_term_number' => 3]);

    expect($subject->courses)->toHaveCount(2)
        ->and($subject->courses->firstWhere('id', $first->id)->pivot->suggested_term_number)->toBe(1)
        ->and($subject->courses->firstWhere('id', $second->id)->pivot->suggested_term_number)->toBe(3)
        ->and($second->subjects->sole()->is($subject))->toBeTrue();
});

test('a subject is linked to a course only once', function () {
    $course = Course::factory()->create();
    $subject = Subject::factory()->forCourse($course)->create();

    expect(fn () => $subject->courses()->attach($course))->toThrow(QueryException::class);
});

test('courses and subjects can have a knowledge area', function () {
    $school = School::factory()->create();
    $area = KnowledgeArea::withoutGlobalScopes()->where('school_id', $school->id)->where('code', 'EXA')->sole();
    $course = Course::factory()->create(['school_id' => $school->id, 'knowledge_area_id' => $area->id]);
    $subject = Subject::factory()->forCourse($course)->create(['knowledge_area_id' => $area->id]);

    expect($course->knowledgeArea->is($area))->toBeTrue()
        ->and($subject->knowledgeArea->is($area))->toBeTrue()
        ->and($area->courses->sole()->is($course))->toBeTrue()
        ->and($area->subjects->sole()->is($subject))->toBeTrue();
});

test('only subjects linked to the course of the classroom can be offered', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    $classroom = Classroom::factory()->create();
    $linked = Subject::factory()->forCourse($classroom->course_id)->create();
    $other = Subject::factory()->forCourse(Course::factory()->create())->create();

    $this->post(route('classroom.subjects.store', $classroom), ['subject_id' => $other->id])
        ->assertSessionHasErrors('subject_id');

    $this->post(route('classroom.subjects.store', $classroom), ['subject_id' => $linked->id])
        ->assertSessionHasNoErrors();

    expect($classroom->classroomSubjects()->pluck('subject_id')->all())->toBe([$linked->id]);
});

test('the migration moves subjects to the catalog and merges duplicates', function () {
    // SQLite rebuilds tables to drop columns; defer the foreign key checks while it does
    DB::statement('PRAGMA defer_foreign_keys = ON');

    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $first = Course::factory()->create(['school_id' => $school->id, 'code' => 'ENG']);
    $second = Course::factory()->create(['school_id' => $school->id, 'code' => 'MAT']);
    $foreign = Course::factory()->create(['school_id' => $otherSchool->id]);
    $classroom = Classroom::factory()->create(['course_id' => $second->id]);
    $teacher = Teacher::factory()->create();

    Artisan::call('migrate:rollback', ['--path' => CATALOG_MIGRATION, '--force' => true]);

    $now = now();
    $legacy = fn (int $courseId, int $schoolId, string $code, string $name, int $term) => DB::table('subjects')->insertGetId([
        'school_id' => $schoolId, 'course_id' => $courseId, 'code' => $code, 'name' => $name,
        'workload_hours' => 60, 'suggested_term_number' => $term, 'created_at' => $now, 'updated_at' => $now,
    ]);

    $calculus = $legacy($first->id, $school->id, 'MAT-101', 'Cálculo I', 1);
    $calculusAgain = $legacy($second->id, $school->id, 'mat-101', 'cálculo i', 2);
    $physics = $legacy($first->id, $school->id, 'FIS-100', 'Física', 1);
    $physicsOther = $legacy($second->id, $school->id, 'FIS-100', 'Física Geral', 3);
    $foreignCalculus = $legacy($foreign->id, $otherSchool->id, 'MAT-101', 'Cálculo I', 1);

    DB::table('classroom_subjects')->insert([
        'classroom_id' => $classroom->id, 'subject_id' => $calculusAgain, 'teacher_id' => $teacher->id,
        'created_at' => $now, 'updated_at' => $now,
    ]);

    Artisan::call('migrate', ['--path' => CATALOG_MIGRATION, '--force' => true]);

    $links = fn (int $subjectId) => DB::table('course_subject')->where('subject_id', $subjectId)->orderBy('course_id')->get(['course_id', 'suggested_term_number']);

    expect(Schema::hasColumns('subjects', ['course_id', 'suggested_term_number']))->toBeFalse()
        ->and(DB::table('subjects')->where('id', $calculusAgain)->value('deleted_at'))->not->toBeNull()
        ->and(DB::table('classroom_subjects')->where('classroom_id', $classroom->id)->value('subject_id'))->toBe($calculus)
        ->and($links($calculus)->pluck('suggested_term_number', 'course_id')->all())->toBe([$first->id => 1, $second->id => 2])
        ->and(DB::table('subjects')->where('id', $physics)->value('code'))->toBe('FIS-100')
        ->and(DB::table('subjects')->where('id', $physicsOther)->value('code'))->toBe('FIS-100-MAT')
        ->and($links($physicsOther)->pluck('course_id')->all())->toBe([$second->id])
        ->and(DB::table('subjects')->where('id', $foreignCalculus)->value('code'))->toBe('MAT-101')
        ->and(DB::table('subjects')->where('id', $foreignCalculus)->value('deleted_at'))->toBeNull()
        ->and(DB::table('knowledge_areas')->where('school_id', $school->id)->count())->toBe(count(KnowledgeArea::DEFAULTS))
        ->and(DB::table('knowledge_areas')->where('school_id', $otherSchool->id)->count())->toBe(count(KnowledgeArea::DEFAULTS));
});
