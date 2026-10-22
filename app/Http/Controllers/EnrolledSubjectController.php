<?php

namespace App\Http\Controllers;

use App\Models\ClassroomSubject;
use App\Repositories\ClassroomSubjectRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lists to a student the subjects of the classrooms they are enrolled in.
 */
class EnrolledSubjectController extends Controller
{
    public function __construct(private readonly ClassroomSubjectRepository $classroomSubjects) {}

    /**
     * Display the subjects of the classrooms the student is actively enrolled in.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewEnrolled', ClassroomSubject::class);

        return Inertia::render('classroom-subject/enrolled', [
            'classroomSubjects' => $this->classroomSubjects->enrolledBy($request->user()),
        ]);
    }
}
