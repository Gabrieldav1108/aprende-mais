<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\Shift;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreClassroomSubjectRequest;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Repositories\AnnouncementRepository;
use App\Repositories\ClassroomSubjectRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomSubjectController extends Controller
{
    public function __construct(
        private readonly ClassroomSubjectRepository $classroomSubjects,
        private readonly AnnouncementRepository $announcements,
    ) {}

    /**
     * Create a subject and attach it to the classroom.
     */
    public function store(StoreClassroomSubjectRequest $request, Classroom $classroom): RedirectResponse
    {
        try {
            $this->classroomSubjects->attachToClassroom($classroom, $request->validated('subject_id'));

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Matéria adicionada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível adicionar a matéria.')]);
        }

        return to_route('classroom.show', $classroom);
    }

    /**
     * Display the subject offered in the classroom with its teacher, announcements and, to staff, its students.
     * Opening the page marks the user's notifications about this classroom subject as read.
     */
    public function show(Request $request, Classroom $classroom, ClassroomSubject $classroomSubject): Response
    {
        $this->authorize('view', $classroomSubject);

        $user = $request->user();

        $user->unreadNotifications()
            ->where('data->classroom_subject_id', $classroomSubject->id)
            ->update(['read_at' => now()]);

        $staffProps = $user->can('students.view') ? [
            'students' => $this->classroomSubjects->studentsFor($classroomSubject),
            'statuses' => EnrollmentStatus::options(),
        ] : [];

        return Inertia::render('classroom-subject/show', [
            'classroomSubject' => $this->classroomSubjects->withDetails($classroomSubject),
            'announcements' => $this->announcements->forClassroomSubject($classroomSubject),
            'canManageAnnouncements' => $user->can('create', [Announcement::class, $classroomSubject]),
            'shifts' => Shift::options(),
            ...$staffProps,
        ]);
    }

    /**
     * Remove the subject from the classroom.
     */
    public function destroy(Classroom $classroom, ClassroomSubject $classroomSubject): RedirectResponse
    {
        $this->authorize('delete', $classroomSubject);

        try {
            $this->classroomSubjects->delete($classroomSubject);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Matéria removida.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível remover a matéria.')]);
        }

        return to_route('classroom.show', $classroom);
    }
}
