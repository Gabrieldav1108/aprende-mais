<?php

namespace App\Http\Controllers;

use App\Enums\TermStatus;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreAcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Models\AcademicTerm;
use App\Repositories\AcademicTermRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicTermController extends Controller
{
    public function __construct(private readonly AcademicTermRepository $terms) {}

    /**
     * Display a listing of the academic terms.
     */
    public function index(): Response
    {
        return Inertia::render('academic-term/index', [
            'terms' => $this->terms->all(),
            'statuses' => TermStatus::options(),
        ]);
    }

    /**
     * Store a newly created academic term.
     */
    public function store(StoreAcademicTermRequest $request): RedirectResponse
    {
        try {
            $this->terms->create($request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Período criado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível criar o período.')]);
        }

        return to_route('academic-term');
    }

    /**
     * Update the given academic term.
     */
    public function update(UpdateAcademicTermRequest $request, AcademicTerm $academicTerm): RedirectResponse
    {
        try {
            $this->terms->update($academicTerm, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Período atualizado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar o período.')]);
        }

        return to_route('academic-term');
    }
}
