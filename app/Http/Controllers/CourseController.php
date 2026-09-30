<?php

namespace App\Http\Controllers;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Repositories\CourseRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(private readonly CourseRepository $courses) {}

    /**
     * Display a listing of the courses.
     */
    public function index(): Response
    {
        return Inertia::render('course/index', [
            'courses' => $this->courses->all(),
            'levels' => CourseLevel::options(),
            'statuses' => CourseStatus::options(),
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        try {
            $this->courses->create($request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Curso criado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível criar o curso.')]);
        }

        return to_route('course');
    }

    /**
     * Display the given course with its subject catalog.
     */
    public function show(Course $course): Response
    {
        return Inertia::render('course/show', [
            'course' => $course,
            'subjects' => $this->courses->subjectsFor($course),
            'levels' => CourseLevel::options(),
            'statuses' => CourseStatus::options(),
        ]);
    }

    /**
     * Update the given course.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        try {
            $this->courses->update($course, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Curso atualizado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar o curso.')]);
        }

        return to_route('course.show', $course);
    }
}
