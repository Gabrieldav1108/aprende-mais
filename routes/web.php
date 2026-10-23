<?php

use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CepController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomSubjectController;
use App\Http\Controllers\CnpjController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrolledSubjectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectCatalogController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInvitationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('convite/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
    Route::post('convite', [AcceptInvitationController::class, 'store'])->middleware('throttle:6,1')->name('invitation.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('cep/{cep}', CepController::class)->middleware('throttle:30,1')->name('cep.show');
    Route::get('cnpj/{cnpj}', CnpjController::class)->middleware('throttle:30,1')->name('cnpj.show');

    Route::get('classroom', [ClassroomController::class, 'index'])->name('classroom');
    Route::post('classroom', [ClassroomController::class, 'store'])->name('classroom.store');
    Route::get('classroom/{classroom}', [ClassroomController::class, 'show'])->name('classroom.show');
    Route::put('classroom/{classroom}', [ClassroomController::class, 'update'])->name('classroom.update');

    Route::get('courses', [CourseController::class, 'index'])->name('course');
    Route::post('courses', [CourseController::class, 'store'])->name('course.store');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('course.show');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('course.update');
    Route::post('courses/{course}/subjects', [SubjectController::class, 'store'])->name('course.subjects.store');

    Route::get('subjects', [SubjectCatalogController::class, 'index'])->name('subject');
    Route::post('subjects', [SubjectCatalogController::class, 'store'])->name('subject.store');
    Route::put('subjects/{subject}', [SubjectCatalogController::class, 'update'])->name('subject.update');

    Route::get('academic-terms', [AcademicTermController::class, 'index'])->name('academic-term');
    Route::post('academic-terms', [AcademicTermController::class, 'store'])->name('academic-term.store');
    Route::put('academic-terms/{academic_term}', [AcademicTermController::class, 'update'])->name('academic-term.update');

    Route::get('students', [StudentController::class, 'index'])->name('student');
    Route::post('students', [StudentController::class, 'store'])->name('student.store');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('student.update');

    Route::get('teachers', [TeacherController::class, 'index'])->name('teacher');
    Route::post('teachers', [TeacherController::class, 'store'])->name('teacher.store');
    Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->name('teacher.update');

    Route::get('users', [UserController::class, 'index'])->name('user');
    Route::post('users', [UserController::class, 'store'])->name('user.store');
    Route::put('users/{person}', [UserController::class, 'update'])->name('user.update');
    Route::post('users/{person}/invitation', [UserInvitationController::class, 'store'])->middleware('throttle:user-invitation')->name('user.invitation');

    Route::scopeBindings()->group(function () {
        Route::post('classroom/{classroom}/subjects', [ClassroomSubjectController::class, 'store'])->name('classroom.subjects.store');
        Route::get('classroom/{classroom}/subjects/{classroomSubject}', [ClassroomSubjectController::class, 'show'])->name('classroom.subjects.show');
        Route::delete('classroom/{classroom}/subjects/{classroomSubject}', [ClassroomSubjectController::class, 'destroy'])->name('classroom.subjects.destroy');

        Route::post('classroom/{classroom}/subjects/{classroomSubject}/announcements', [AnnouncementController::class, 'store'])->name('classroom.subjects.announcements.store');
        Route::put('classroom/{classroom}/subjects/{classroomSubject}/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('classroom.subjects.announcements.update');
        Route::delete('classroom/{classroom}/subjects/{classroomSubject}/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('classroom.subjects.announcements.destroy');
    });

    Route::get('my-subjects', [EnrolledSubjectController::class, 'index'])->name('enrolled-subject');

    Route::post('attachments', [AttachmentController::class, 'store'])->middleware('throttle:30,1')->name('attachments.store');
    Route::post('attachments/{attachment}/complete', [AttachmentController::class, 'complete'])->name('attachments.complete');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notification.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notification.read');
});

require __DIR__.'/settings.php';
