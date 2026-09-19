<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomSubjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('classroom', [ClassroomController::class, 'index'])->name('classroom');
    Route::post('classroom', [ClassroomController::class, 'store'])->name('classroom.store');
    Route::get('classroom/{classroom}', [ClassroomController::class, 'show'])->name('classroom.show');
    Route::put('classroom/{classroom}', [ClassroomController::class, 'update'])->name('classroom.update');

    Route::scopeBindings()->group(function () {
        Route::post('classroom/{classroom}/subjects', [ClassroomSubjectController::class, 'store'])->name('classroom.subjects.store');
        Route::delete('classroom/{classroom}/subjects/{classroomSubject}', [ClassroomSubjectController::class, 'destroy'])->name('classroom.subjects.destroy');
    });
});

require __DIR__.'/settings.php';
