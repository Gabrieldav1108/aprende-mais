<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Repositories\AnnouncementRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementRepository $announcements) {}

    /**
     * Post an announcement to the classroom subject and notify its students.
     */
    public function store(StoreAnnouncementRequest $request, Classroom $classroom, ClassroomSubject $classroomSubject): RedirectResponse
    {
        try {
            $this->announcements->publish($classroomSubject, $request->user(), $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Aviso publicado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível publicar o aviso.')]);
        }

        return to_route('classroom.subjects.show', [$classroom, $classroomSubject]);
    }

    /**
     * Update the announcement's title and body.
     */
    public function update(UpdateAnnouncementRequest $request, Classroom $classroom, ClassroomSubject $classroomSubject, Announcement $announcement): RedirectResponse
    {
        try {
            $this->announcements->update($announcement, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Aviso atualizado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar o aviso.')]);
        }

        return to_route('classroom.subjects.show', [$classroom, $classroomSubject]);
    }

    /**
     * Remove the announcement.
     */
    public function destroy(Classroom $classroom, ClassroomSubject $classroomSubject, Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        try {
            $this->announcements->delete($announcement);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Aviso excluído.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível excluir o aviso.')]);
        }

        return to_route('classroom.subjects.show', [$classroom, $classroomSubject]);
    }
}
