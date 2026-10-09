<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreCatalogSubjectRequest;
use App\Http\Requests\UpdateCatalogSubjectRequest;
use App\Models\KnowledgeArea;
use App\Models\Subject;
use App\Repositories\SubjectRepository;
use App\Repositories\TeacherRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubjectCatalogController extends Controller
{
    public function __construct(
        private readonly SubjectRepository $subjects,
        private readonly TeacherRepository $teachers,
    ) {}

    /**
     * Display the subject catalog of the school.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Subject::class);

        return Inertia::render('subjects/index', [
            'subjects' => $this->subjects->all(),
            'teachers' => $this->teachers->allWithPerson(),
            'knowledgeAreas' => KnowledgeArea::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly registered subject.
     */
    public function store(StoreCatalogSubjectRequest $request): RedirectResponse
    {
        try {
            $this->subjects->create($request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Matéria cadastrada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível cadastrar a matéria.')]);
        }

        return to_route('subject');
    }

    /**
     * Update the given subject.
     */
    public function update(UpdateCatalogSubjectRequest $request, Subject $subject): RedirectResponse
    {
        try {
            $this->subjects->update($subject, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Matéria atualizada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar a matéria.')]);
        }

        return to_route('subject');
    }
}
