<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Repositories\CourseRepository;
use App\Repositories\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly CourseRepository $courses,
    ) {}

    /**
     * Display a listing of the students.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Student::class);

        return Inertia::render('student/index', [
            'students' => $this->students->all(),
            'courses' => $this->courses->all()->map->only(['id', 'name']),
            'statuses' => StudentStatus::options(),
        ]);
    }

    /**
     * Store a newly registered student.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        try {
            $this->students->create($request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Aluno cadastrado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível cadastrar o aluno.')]);
        }

        return to_route('student');
    }

    /**
     * Update the given student.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        try {
            $this->students->update($student, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Aluno atualizado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar o aluno.')]);
        }

        return to_route('student');
    }
}
