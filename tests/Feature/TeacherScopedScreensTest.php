<?php

use App\Enums\ClassroomStatus;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * A school where one teacher teaches some classrooms and another teacher teaches the rest.
 *
 * @return array<string, mixed>
 */
function teachingScenario(): array
{
    $school = School::factory()->create();
    $teacher = Teacher::factory()->inSchool($school->id)->create();
    $otherTeacher = Teacher::factory()->inSchool($school->id)->create();
    $course = Course::factory()->create(['school_id' => $school->id]);

    $subject = Subject::factory()->forCourse($course)->create(['teacher_id' => $teacher->id]);
    $otherSubject = Subject::factory()->forCourse($course)->create(['teacher_id' => $otherTeacher->id]);

    $taught = classroomTaughtBy($course, $subject, $teacher);
    $homeroom = Classroom::factory()->create(['course_id' => $course->id, 'homeroom_teacher_id' => $teacher->id]);
    $finished = classroomTaughtBy($course, $subject, $teacher, ClassroomStatus::Finished);
    $others = classroomTaughtBy($course, $otherSubject, $otherTeacher);

    return [
        'school' => $school,
        'teacher' => $teacher->person->user,
        'subject' => $subject,
        'otherSubject' => $otherSubject,
        'taught' => $taught,
        'homeroom' => $homeroom,
        'finished' => $finished,
        'others' => $others,
        'studentOfTaught' => studentEnrolledIn($taught),
        'studentOfHomeroom' => studentEnrolledIn($homeroom),
        'studentOfFinished' => studentEnrolledIn($finished),
        'studentOfOthers' => studentEnrolledIn($others),
    ];
}

function classroomTaughtBy(Course $course, Subject $subject, Teacher $teacher, ClassroomStatus $status = ClassroomStatus::Active): Classroom
{
    $classroom = Classroom::factory()->create(['course_id' => $course->id, 'status' => $status]);
    ClassroomSubject::create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

    return $classroom;
}

function studentEnrolledIn(Classroom $classroom): Student
{
    $student = Student::factory()->inSchool($classroom->school_id)->create();
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => 'active']);

    return $student;
}

/**
 * The sorted ids of the records an Inertia page received in the given prop.
 *
 * @return list<int>
 */
function idsInProp(TestResponse $response, string $prop): array
{
    $ids = [];

    $response->assertOk()->assertInertia(function (AssertableInertia $page) use ($prop, &$ids): void {
        $page->where($prop, function (Collection $records) use (&$ids): bool {
            $ids = $records->pluck('id')->sort()->values()->all();

            return true;
        });
    });

    return $ids;
}

/**
 * @param  list<Model>  $models
 * @return list<int>
 */
function sortedIds(array $models): array
{
    return collect($models)->pluck('id')->sort()->values()->all();
}

test('a teacher sees only the subjects they teach', function () {
    $scenario = teachingScenario();

    $ids = idsInProp($this->actingAs($scenario['teacher'])->get(route('subject')), 'subjects');

    expect($ids)->toBe([$scenario['subject']->id]);
});

test('a teacher sees the classrooms where they teach a subject or are the homeroom teacher', function () {
    $scenario = teachingScenario();

    $ids = idsInProp($this->actingAs($scenario['teacher'])->get(route('classroom')), 'classrooms');

    expect($ids)->toBe(sortedIds([$scenario['taught'], $scenario['homeroom'], $scenario['finished']]));
});

test('a teacher can open their own classroom but not another teacher\'s', function () {
    $scenario = teachingScenario();
    $this->actingAs($scenario['teacher']);

    $this->get(route('classroom.show', $scenario['taught']))->assertOk();
    $this->get(route('classroom.show', $scenario['homeroom']))->assertOk();
    $this->get(route('classroom.show', $scenario['others']))->assertNotFound();
});

test('a teacher sees only the students of the active classrooms they teach', function () {
    $scenario = teachingScenario();

    $ids = idsInProp($this->actingAs($scenario['teacher'])->get(route('student')), 'students');

    expect($ids)->toBe(sortedIds([$scenario['studentOfTaught'], $scenario['studentOfHomeroom']]));
});

test('an admin still sees every subject, classroom and student', function () {
    $scenario = teachingScenario();
    $this->actingAs(User::factory()->admin()->create(['school_id' => $scenario['school']->id]));

    expect(idsInProp($this->get(route('subject')), 'subjects'))->toBe(sortedIds([$scenario['subject'], $scenario['otherSubject']]))
        ->and(idsInProp($this->get(route('classroom')), 'classrooms'))->toBe(sortedIds([$scenario['taught'], $scenario['homeroom'], $scenario['finished'], $scenario['others']]))
        ->and(idsInProp($this->get(route('student')), 'students'))->toBe(sortedIds([$scenario['studentOfTaught'], $scenario['studentOfHomeroom'], $scenario['studentOfFinished'], $scenario['studentOfOthers']]));

    $this->get(route('classroom.show', $scenario['others']))->assertOk();
});

test('a user with the teacher role but no teacher record sees no records', function () {
    $scenario = teachingScenario();
    $this->actingAs(User::factory()->withRole('teacher')->create(['school_id' => $scenario['school']->id]));

    expect(idsInProp($this->get(route('subject')), 'subjects'))->toBe([])
        ->and(idsInProp($this->get(route('classroom')), 'classrooms'))->toBe([])
        ->and(idsInProp($this->get(route('student')), 'students'))->toBe([]);
});
