<?php

use App\Enums\ClassroomStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Course;
use App\Models\KnowledgeArea;
use App\Models\Person;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\AcademicTermSeeder;
use Database\Seeders\ClassroomSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\KnowledgeAreaSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\TeacherSeeder;

dataset('screen seeders', [
    'knowledge areas' => [KnowledgeAreaSeeder::class, KnowledgeArea::class, count(KnowledgeArea::DEFAULTS)],
    'courses' => [CourseSeeder::class, Course::class, count(CourseSeeder::COURSES)],
    'teachers' => [TeacherSeeder::class, Teacher::class, count(TeacherSeeder::TEACHERS)],
    'subjects' => [SubjectSeeder::class, Subject::class, count(SubjectSeeder::SUBJECTS)],
    'academic terms' => [AcademicTermSeeder::class, AcademicTerm::class, count(AcademicTermSeeder::TERMS)],
    'students' => [StudentSeeder::class, Student::class, count(StudentSeeder::STUDENTS)],
    'classrooms' => [ClassroomSeeder::class, Classroom::class, count(ClassroomSeeder::CLASSROOMS)],
]);

test('each seeder runs on its own and can run twice without duplicating records', function (string $seeder, string $model, int $expected) {
    $this->seed($seeder);
    $this->seed($seeder);

    expect($model::count())->toBe($expected);
})->with('screen seeders');

test('subjects are linked to their courses with the suggested term and a teacher', function () {
    $this->seed(SubjectSeeder::class);

    $logic = Subject::where('code', 'LOG-101')->sole();

    expect($logic->teacher_id)->not->toBeNull()
        ->and($logic->courses->pluck('pivot.suggested_term_number', 'code')->all())->toEqual(['ADS' => 1, 'ESW' => 1]);
});

test('classrooms offer the subjects of their term and enroll the course students', function () {
    $this->seed(ClassroomSeeder::class);

    $classroom = Classroom::where('code', 'ADS-2N-262')->sole();
    $expectedSubjects = collect(SubjectSeeder::SUBJECTS)->filter(fn (array $subject) => ($subject['courses']['ADS'] ?? null) === 2)->count();

    expect(ClassroomSubject::where('classroom_id', $classroom->id)->count())->toBe($expectedSubjects)
        ->and(ClassroomStudent::where('classroom_id', $classroom->id)->where('status', EnrollmentStatus::Active)->count())->toBe(4)
        ->and(ClassroomStudent::whereRelation('classroom', 'status', ClassroomStatus::Finished)->pluck('status')->unique()->all())->toEqual([EnrollmentStatus::Completed])
        ->and(ClassroomStudent::whereRelation('classroom', 'status', ClassroomStatus::Planned)->count())->toBe(0);
});

test('seeded teachers and students have a role, personal data and a unique document', function () {
    $this->seed([TeacherSeeder::class, StudentSeeder::class]);

    $people = Person::with(['user', 'address', 'phones', 'emails'])->get();

    expect($people)->toHaveCount(count(TeacherSeeder::TEACHERS) + count(StudentSeeder::STUDENTS))
        ->and($people->pluck('cpf_cnpj')->unique())->toHaveCount($people->count())
        ->and($people->every(fn (Person $person) => $person->address && $person->phones->isNotEmpty() && $person->emails->isNotEmpty()))->toBeTrue()
        ->and(User::role('teacher')->count())->toBe(count(TeacherSeeder::TEACHERS))
        ->and(User::role('student')->count())->toBe(count(StudentSeeder::STUDENTS));
});

test('every screen opens for an admin with the seeded data', function (string $route) {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::where('email', 'gabriel.admin@aprendemais.com')->sole())
        ->get(route($route))
        ->assertOk();
})->with(['course', 'subject', 'academic-term', 'classroom', 'student', 'teacher', 'user']);

test('the classroom and course pages open for an admin with the seeded data', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'gabriel.admin@aprendemais.com')->sole());

    $this->get(route('classroom.show', Classroom::where('code', 'ADS-2N-262')->sole()))->assertOk();
    $this->get(route('course.show', Course::where('code', 'ADS')->sole()))->assertOk();
});
