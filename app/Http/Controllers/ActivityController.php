<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Repositories\ActivityRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function __construct(private readonly ActivityRepository $activities) {}

    /**
     * Post an activity to the classroom subject and notify its students.
     */
    public function store(StoreActivityRequest $request, Classroom $classroom, ClassroomSubject $classroomSubject): RedirectResponse
    {
        try {
            $this->activities->publish($classroomSubject, $request->user(), $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Atividade publicada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível publicar a atividade.')]);
        }

        return $this->toActivities($classroom, $classroomSubject);
    }

    /**
     * Update the activity's title, description, due date and supporting files.
     */
    public function update(UpdateActivityRequest $request, Classroom $classroom, ClassroomSubject $classroomSubject, Activity $activity): RedirectResponse
    {
        try {
            $this->activities->update($activity, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Atividade atualizada.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar a atividade.')]);
        }

        return $this->toActivities($classroom, $classroomSubject);
    }

    /**
     * Soft delete the activity.
     */
    public function destroy(Classroom $classroom, ClassroomSubject $classroomSubject, Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        try {
            $this->activities->delete($activity);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Atividade excluída.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível excluir a atividade.')]);
        }

        return $this->toActivities($classroom, $classroomSubject);
    }

    /**
     * Back to the activities tab of the classroom subject page.
     */
    private function toActivities(Classroom $classroom, ClassroomSubject $classroomSubject): RedirectResponse
    {
        return to_route('classroom.subjects.show', [$classroom, $classroomSubject, 'tab' => 'activities']);
    }
}
