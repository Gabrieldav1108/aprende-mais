<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Activity;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Repositories\ClassroomSubjectRepository;
use App\Repositories\SubmissionRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionRepository $submissions,
        private readonly ClassroomSubjectRepository $classroomSubjects,
    ) {}

    /**
     * Display every student of the activity with all the versions they sent.
     */
    public function index(Classroom $classroom, ClassroomSubject $classroomSubject, Activity $activity): Response
    {
        $this->authorize('viewSubmissions', $activity);

        return Inertia::render('activity/submissions', [
            'classroomSubject' => $this->classroomSubjects->withDetails($classroomSubject),
            'activity' => $activity->load(['attachments:id,attachable_type,attachable_id,original_name,mime_type,size']),
            'students' => $this->submissions->forActivity($activity),
        ]);
    }

    /**
     * Store a new version of the student's answer to the activity.
     */
    public function store(StoreSubmissionRequest $request, Classroom $classroom, ClassroomSubject $classroomSubject, Activity $activity): RedirectResponse
    {
        $student = $request->user()->student;
        $isChange = $activity->submissions()->where('student_id', $student->id)->exists();

        try {
            $this->submissions->submit($activity, $student, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => $isChange ? __('Entrega alterada.') : __('Atividade entregue.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível entregar a atividade.')]);
        }

        return to_route('classroom.subjects.show', [$classroom, $classroomSubject, 'tab' => 'activities']);
    }
}
