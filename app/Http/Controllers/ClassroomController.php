<?php

namespace App\Http\Controllers;

use App\Enums\ClassroomStatus;
use App\Enums\Shift;
use App\Enums\TermStatus;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\Course;
use App\Repositories\ClassroomRepository;
use App\Repositories\CourseRepository;
use App\Repositories\TeacherRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function __construct(
        private readonly ClassroomRepository $classrooms,
        private readonly CourseRepository $courses,
        private readonly TeacherRepository $teachers,
    ) {}

    /**
     * Display a listing of the classrooms.
     */
    public function index(): Response
    {
        return Inertia::render('classroom/index', [
            'classrooms' => $this->classrooms->all(),
            ...$this->formOptions(),
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
        return Inertia::render('classroom/show', [
            'classroom' => $this->classrooms->withDetails($classroom),
            'classroomSubjects' => $this->classrooms->subjectsFor($classroom),
            'courseSubjects' => $this->courses->subjectsFor($classroom->course),
            ...$this->formOptions(),
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

    /**
     * Options needed by the classroom form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'courses' => Course::query()->orderBy('name')->get(['id', 'name', 'code', 'duration_terms']),
            'terms' => AcademicTerm::query()
                ->whereIn('status', [TermStatus::Planned, TermStatus::Open])
                ->orderByDesc('year')->orderByDesc('number')
                ->get(['id', 'name']),
            'teachers' => $this->teachers->allWithPerson(),
            'shifts' => Shift::options(),
            'statuses' => ClassroomStatus::options(),
        ];
    }
}
