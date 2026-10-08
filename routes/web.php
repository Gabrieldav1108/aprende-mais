<?php

use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomSubjectController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('classroom', [ClassroomController::class, 'index'])->name('classroom');
    Route::post('classroom', [ClassroomController::class, 'store'])->name('classroom.store');
    Route::get('classroom/{classroom}', [ClassroomController::class, 'show'])->name('classroom.show');
    Route::put('classroom/{classroom}', [ClassroomController::class, 'update'])->name('classroom.update');

    Route::get('courses', [CourseController::class, 'index'])->name('course');
    Route::post('courses', [CourseController::class, 'store'])->name('course.store');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('course.show');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('course.update');
    Route::post('courses/{course}/subjects', [SubjectController::class, 'store'])->name('course.subjects.store');

    Route::get('academic-terms', [AcademicTermController::class, 'index'])->name('academic-term');
    Route::post('academic-terms', [AcademicTermController::class, 'store'])->name('academic-term.store');
    Route::put('academic-terms/{academic_term}', [AcademicTermController::class, 'update'])->name('academic-term.update');

    Route::get('students', [StudentController::class, 'index'])->name('student');
    Route::post('students', [StudentController::class, 'store'])->name('student.store');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('student.update');

    Route::scopeBindings()->group(function () {
        Route::post('classroom/{classroom}/subjects', [ClassroomSubjectController::class, 'store'])->name('classroom.subjects.store');
        Route::delete('classroom/{classroom}/subjects/{classroomSubject}', [ClassroomSubjectController::class, 'destroy'])->name('classroom.subjects.destroy');
    });
});

require __DIR__.'/settings.php';
