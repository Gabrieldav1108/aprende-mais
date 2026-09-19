<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreClassroomSubjectRequest;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Repositories\ClassroomSubjectRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ClassroomSubjectController extends Controller
{
    public function __construct(private readonly ClassroomSubjectRepository $classroomSubjects) {}

    /**
     * Create a subject and attach it to the classroom with a teacher.
     */
    public function store(StoreClassroomSubjectRequest $request, Classroom $classroom): RedirectResponse
    {
        try {
            $this->classroomSubjects->createForClassroom(
                $classroom,
                $request->safe()->only(['name', 'code', 'workload_hours']),
                $request->validated('teacher_id'),
            );

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Matéria adicionada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível adicionar a matéria.')]);
        }

        return to_route('classroom.show', $classroom);
    }

    /**
     * Remove the subject from the classroom.
     */
    public function destroy(Classroom $classroom, ClassroomSubject $classroomSubject): RedirectResponse
    {
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
