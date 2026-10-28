<?php

use App\Enums\EnrollmentStatus;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Person;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Storage::fake(Attachment::DISK);
    Storage::disk(Attachment::DISK)->buildTemporaryUrlsUsing(
        fn (string $path) => "https://bucket.test/{$path}?download-signature",
    );
});

/**
 * An activity of a subject offered in a new classroom, taught by a new teacher of the same school.
 *
 * @param  array<string, mixed>  $attributes
 */
function submittedActivity(array $attributes = [], ?Classroom $classroom = null): Activity
{
    $classroom ??= Classroom::factory()->create();
    $classroomSubject = ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);

    return Activity::factory()->forClassroomSubject($classroomSubject)->create($attributes);
}

function submissionTeacher(Activity $activity): User
{
    return Teacher::find($activity->classroomSubject->teacher_id)->person->user;
}

function submittingStudent(Classroom $classroom, string $name = 'Ana', EnrollmentStatus $status = EnrollmentStatus::Active): Student
{
    $student = Student::factory()->create([
        'person_id' => Person::factory()->inSchool($classroom->school_id)->create(['name' => $name])->id,
    ]);
    ClassroomStudent::create(['classroom_id' => $classroom->id, 'student_id' => $student->id, 'enrolled_at' => now(), 'status' => $status]);

    return $student;
}

/**
 * A file the user uploaded and confirmed, with its object in the fake bucket, linked to the record when given.
 */
function submissionFileOf(User $uploader, ?Submission $submission = null): Attachment
{
    $attachment = Attachment::factory()->uploaded()->create(['uploader_id' => $uploader->id]);
    Storage::disk(Attachment::DISK)->put($attachment->path, "content of {$attachment->original_name}");

    if ($submission !== null) {
        $attachment->attachable()->associate($submission)->save();
    }

    return $attachment;
}

function submissionsUrl(string $action, Activity $activity): string
{
    return route("classroom.subjects.activities.submissions.{$action}", [$activity->classroomSubject->classroom_id, $activity->classroom_subject_id, $activity]);
}

test('an enrolled student submits an answer with files', function () {
    $activity = submittedActivity(['due_at' => now()->addWeek()]);
    $student = submittingStudent($activity->classroomSubject->classroom);
    $files = [submissionFileOf($student->person->user), submissionFileOf($student->person->user)];

    $this->actingAs($student->person->user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Minha resposta', 'attachment_ids' => [$files[0]->id, $files[1]->id]])
        ->assertRedirect(route('classroom.subjects.show', [$activity->classroomSubject->classroom_id, $activity->classroom_subject_id, 'tab' => 'activities']))
        ->assertInertiaFlash('toast.message', 'Atividade entregue.');

    $submission = Submission::sole();

    expect($submission)
        ->activity_id->toBe($activity->id)
        ->student_id->toBe($student->id)
        ->content->toBe('Minha resposta')
        ->and($submission->attachments()->pluck('id')->sort()->values()->all())->toBe([$files[0]->id, $files[1]->id]);
});

test('an answer needs a text or a file', function () {
    $activity = submittedActivity();
    $student = submittingStudent($activity->classroomSubject->classroom);

    $this->actingAs($student->person->user)
        ->post(submissionsUrl('store', $activity), ['content' => '', 'attachment_ids' => []])
        ->assertSessionHasErrors('content');

    $this->post(submissionsUrl('store', $activity), ['attachment_ids' => [submissionFileOf($student->person->user)->id]])
        ->assertSessionHasNoErrors();

    expect(Submission::sole()->content)->toBeNull();
});

test('the first answer is accepted after the due date', function () {
    $activity = submittedActivity(['due_at' => now()->subDay()]);
    $student = submittingStudent($activity->classroomSubject->classroom);

    $this->actingAs($student->person->user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Atrasada'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.type', 'success');

    expect(Submission::count())->toBe(1);
});

test('changing the answer before the due date stores a new version and keeps the earlier one with its files', function () {
    $activity = submittedActivity(['due_at' => now()->addWeek()]);
    $student = submittingStudent($activity->classroomSubject->classroom);
    $user = $student->person->user;
    $first = Submission::factory()->forActivity($activity)->by($student)->create(['content' => 'Primeira']);
    [$kept, $dropped] = [submissionFileOf($user, $first), submissionFileOf($user, $first)];
    $added = submissionFileOf($user);

    $this->actingAs($user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Segunda', 'attachment_ids' => [$kept->id, $added->id]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Entrega alterada.');

    $second = Submission::query()->latest('id')->first();
    $copy = $second->attachments()->where('original_name', $kept->original_name)->sole();

    expect(Submission::count())->toBe(2)
        ->and($second->content)->toBe('Segunda')
        ->and($second->attachments()->count())->toBe(2)
        ->and($added->fresh()->attachable_id)->toBe($second->id)
        ->and($copy->id)->not->toBe($kept->id)
        ->and($copy->path)->not->toBe($kept->path)
        ->and($first->fresh()->content)->toBe('Primeira')
        ->and($first->attachments()->pluck('id')->sort()->values()->all())->toBe(collect([$kept->id, $dropped->id])->sort()->values()->all());
    Storage::disk(Attachment::DISK)->assertExists([$kept->path, $dropped->path, $copy->path]);
    expect(Storage::disk(Attachment::DISK)->get($copy->path))->toBe("content of {$kept->original_name}");
});

test('the answer cannot be changed once the due date has passed', function () {
    $activity = submittedActivity(['due_at' => now()->subMinute()]);
    $student = submittingStudent($activity->classroomSubject->classroom);
    Submission::factory()->forActivity($activity)->by($student)->create();

    $this->actingAs($student->person->user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Mudei de ideia'])
        ->assertForbidden();

    expect(Submission::count())->toBe(1);
});

test('without a due date the answer can always be changed', function () {
    $activity = submittedActivity(['due_at' => null]);
    $student = submittingStudent($activity->classroomSubject->classroom);
    Submission::factory()->forActivity($activity)->by($student)->create(['created_at' => now()->subMonth()]);

    $this->actingAs($student->person->user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Nova versão'])
        ->assertSessionHasNoErrors();

    expect(Submission::count())->toBe(2);
});

test('only a student actively enrolled in the classroom may answer', function (string $who) {
    $activity = submittedActivity();
    $classroom = $activity->classroomSubject->classroom;

    $user = match ($who) {
        'a student on hold' => submittingStudent($classroom, status: EnrollmentStatus::OnHold)->person->user,
        'a student of another classroom' => submittingStudent(Classroom::factory()->create(['course_id' => $classroom->course_id]))->person->user,
        'the teacher' => submissionTeacher($activity),
        'an admin' => User::factory()->admin()->create(['school_id' => $classroom->school_id]),
    };

    $this->actingAs($user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Resposta'])
        ->assertForbidden();

    expect(Submission::count())->toBe(0);
})->with(['a student on hold', 'a student of another classroom', 'the teacher', 'an admin']);

test('an answer only takes files the student uploaded that are free or belong to their current answer', function (string $file) {
    $activity = submittedActivity();
    $classroom = $activity->classroomSubject->classroom;
    $student = submittingStudent($classroom);
    $user = $student->person->user;

    $attachment = match ($file) {
        'of another user' => submissionFileOf(User::factory()->create()),
        'still pending' => Attachment::factory()->pending()->create(['uploader_id' => $user->id]),
        'of another student answer' => submissionFileOf(
            $other = submittingStudent($classroom, 'Bruno')->person->user,
            Submission::factory()->forActivity($activity)->by($other->student)->create(),
        ),
        'of an earlier version' => tap(
            submissionFileOf($user, Submission::factory()->forActivity($activity)->by($student)->create()),
            fn () => Submission::factory()->forActivity($activity)->by($student)->create(),
        ),
    };

    $this->actingAs($user)
        ->post(submissionsUrl('store', $activity), ['content' => 'Resposta', 'attachment_ids' => [$attachment->id]])
        ->assertSessionHasErrors('attachment_ids.0');

    expect(Submission::query()->where('content', 'Resposta')->exists())->toBeFalse();
})->with(['of another user', 'still pending', 'of another student answer', 'of an earlier version']);

test('the teacher sees every student of the activity by name with all the versions they sent, newest first', function () {
    $activity = submittedActivity();
    $classroom = $activity->classroomSubject->classroom;
    $bruno = submittingStudent($classroom, 'Bruno');
    $ana = submittingStudent($classroom, 'Ana');
    $carla = submittingStudent($classroom, 'Carla');
    ClassroomStudent::where('student_id', $carla->id)->update(['status' => EnrollmentStatus::OnHold]);
    submittingStudent($classroom, 'Diego', EnrollmentStatus::OnHold);
    $older = Submission::factory()->forActivity($activity)->by($ana)->create();
    $newer = Submission::factory()->forActivity($activity)->by($ana)->create();
    $file = submissionFileOf($ana->person->user, $newer);
    $carlaAnswer = Submission::factory()->forActivity($activity)->by($carla)->create();
    Submission::factory()->forActivity(submittedActivity(classroom: $classroom))->by($bruno)->create();

    $this->actingAs(submissionTeacher($activity))
        ->get(submissionsUrl('index', $activity))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('activity/submissions')
            ->where('activity.id', $activity->id)
            ->has('students', 3)
            ->where('students.0.person.name', 'Ana')
            ->where('students.0.submissions.0.id', $newer->id)
            ->where('students.0.submissions.0.attachments.0.id', $file->id)
            ->missing('students.0.submissions.0.attachments.0.path')
            ->where('students.0.submissions.1.id', $older->id)
            ->where('students.1.person.name', 'Bruno')
            ->has('students.1.submissions', 0)
            ->where('students.2.person.name', 'Carla')
            ->where('students.2.submissions.0.id', $carlaAnswer->id));
});

test('another teacher or a student cannot see the submissions, an admin can', function (string $who, int $status) {
    $activity = submittedActivity();
    $classroom = $activity->classroomSubject->classroom;

    $user = match ($who) {
        'another teacher' => submissionTeacher(submittedActivity(classroom: $classroom)),
        'a student' => submittingStudent($classroom)->person->user,
        'an admin' => User::factory()->admin()->create(['school_id' => $classroom->school_id]),
    };

    $this->actingAs($user)->get(submissionsUrl('index', $activity))->assertStatus($status);
})->with([
    ['another teacher', 403],
    ['a student', 403],
    ['an admin', 200],
]);

test('the student and the teacher download the files of the answer, another student does not', function () {
    $activity = submittedActivity();
    $classroom = $activity->classroomSubject->classroom;
    $student = submittingStudent($classroom);
    $file = submissionFileOf($student->person->user, Submission::factory()->forActivity($activity)->by($student)->create());

    $this->actingAs($student->person->user)->get(route('attachments.download', $file))->assertRedirect();
    $this->actingAs(submissionTeacher($activity))->get(route('attachments.download', $file))->assertRedirect();
    $this->actingAs(submittingStudent($classroom, 'Bruno')->person->user)->get(route('attachments.download', $file))->assertForbidden();

    $activity->delete();

    $this->actingAs($student->person->user)->get(route('attachments.download', $file->fresh()))->assertForbidden();
});

test('a student sees only their own answers to each activity on the classroom subject page', function () {
    $activity = submittedActivity(['due_at' => now()->addWeek()]);
    $classroomSubject = $activity->classroomSubject;
    $student = submittingStudent($classroomSubject->classroom);
    $older = Submission::factory()->forActivity($activity)->by($student)->create();
    $newer = Submission::factory()->forActivity($activity)->by($student)->create();
    Submission::factory()->forActivity($activity)->by(submittingStudent($classroomSubject->classroom, 'Bruno'))->create();

    $this->actingAs($student->person->user)
        ->get(route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canSubmitActivities', true)
            ->has('activities.0.submissions', 2)
            ->where('activities.0.submissions.0.id', $newer->id)
            ->where('activities.0.submissions.1.id', $older->id)
            ->missing('activities.0.submitted_students_count'));
});

test('the teacher sees how many students answered each activity on the classroom subject page', function () {
    $activity = submittedActivity();
    $classroomSubject = $activity->classroomSubject;
    $ana = submittingStudent($classroomSubject->classroom);
    Submission::factory()->forActivity($activity)->by($ana)->count(2)->create();
    Submission::factory()->forActivity($activity)->by(submittingStudent($classroomSubject->classroom, 'Bruno'))->create();

    $this->actingAs(submissionTeacher($activity))
        ->get(route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canSubmitActivities', false)
            ->where('activities.0.submitted_students_count', 2)
            ->missing('activities.0.submissions'));
});
