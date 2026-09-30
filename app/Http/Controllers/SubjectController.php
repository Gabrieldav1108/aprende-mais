<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreSubjectRequest;
use App\Models\Course;
use App\Repositories\CourseRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function __construct(private readonly CourseRepository $courses) {}

    /**
     * Add a subject to the course catalog.
     */
    public function store(StoreSubjectRequest $request, Course $course): RedirectResponse
    {
        try {
            $this->courses->createSubject($course, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Matéria adicionada ao curso.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível adicionar a matéria.')]);
        }

        return to_route('course.show', $course);
    }
}
