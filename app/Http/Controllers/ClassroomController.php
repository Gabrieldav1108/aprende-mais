<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Repositories\ClassroomRepository;
use App\Repositories\TeacherRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function __construct(
        private readonly ClassroomRepository $classrooms,
        private readonly TeacherRepository $teachers,
    ) {}

    /**
     * Display a listing of the classrooms.
     */
    public function index(): Response
    {
        return Inertia::render('classroom', [
            'classrooms' => $this->classrooms->all(),
        ]);
    }

    /**
     * Store a newly created classroom.
     */
    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        try {
            $this->classrooms->create($request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Turma criada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível criar a turma.')]);
        }

        return to_route('classroom');
    }

    /**
     * Display the given classroom with its subjects and teachers.
     */
    public function show(Classroom $classroom): Response
    {
        return Inertia::render('classroom-show', [
            'classroom' => $classroom,
            'classroomSubjects' => $this->classrooms->subjectsFor($classroom),
            'teachers' => $this->teachers->allWithUser(),
        ]);
    }

    /**
     * Update the given classroom.
     */
    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        try {
            $this->classrooms->update($classroom, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Turma atualizada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar a turma.')]);
        }

        return to_route('classroom.show', $classroom);
    }
}
