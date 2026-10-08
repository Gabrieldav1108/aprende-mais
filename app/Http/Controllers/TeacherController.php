<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Repositories\PersonRepository;
use App\Repositories\TeacherRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function __construct(
        private readonly TeacherRepository $teachers,
        private readonly PersonRepository $people,
    ) {}

    /**
     * Display a listing of the teachers.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Teacher::class);

        return Inertia::render('teacher/index', [
            'teachers' => $this->teachers->all(),
            'people' => $this->people->availableFor(UserRole::Teacher->value),
        ]);
    }

    /**
     * Store a newly registered teacher.
     */
    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        try {
            $this->teachers->create($request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Professor cadastrado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível cadastrar o professor.')]);
        }

        return to_route('teacher');
    }

    /**
     * Update the given teacher.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        try {
            $this->teachers->update($teacher, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Professor atualizado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar o professor.')]);
        }

        return to_route('teacher');
    }
}
