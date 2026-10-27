<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Support\DemoData;
use Illuminate\Support\Facades\Route;

// Frontend bosqichi: vaqtinchalik demo ma'lumotlar bilan.
// Keyingi bosqichda controller'lar va haqiqiy ma'lumotlarga almashtiriladi.

Route::view('/', 'pages.home', ['courses' => array_slice(DemoData::courses(), 0, 3)])->name('home');

Route::get('/kurslar', fn () => view('pages.courses', ['courses' => DemoData::courses()]))->name('courses.index');

Route::get('/kurslar/{slug}', function (string $slug) {
    $course = DemoData::course($slug);
    abort_if(! $course, 404);

    return view('pages.course', ['course' => $course]);
})->name('courses.show');

Route::get('/kurslar/{slug}/darslar/{id}', function (string $slug, int $id) {
    $course = DemoData::course($slug);
    abort_if(! $course, 404);
    $lesson = collect($course['lessons'])->firstWhere('id', $id);
    abort_if(! $lesson, 404);

    return view('pages.lesson', compact('course', 'lesson'));
})->name('lessons.show');

// Autentifikatsiya
Route::middleware('guest')->group(function () {
    Route::get('/kirish', [LoginController::class, 'create'])->name('login');
    Route::post('/kirish', [LoginController::class, 'store'])->name('login.store');
    Route::get('/royxatdan-otish', [RegisterController::class, 'create'])->name('register');
    Route::post('/royxatdan-otish', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/chiqish', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Admin (repetitor)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('courses', AdminCourseController::class);
    Route::resource('courses.lessons', AdminLessonController::class)->shallow()->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::post('lessons/{lesson}/move/{direction}', [AdminLessonController::class, 'move'])->name('lessons.move');
    Route::get('lessons/{lesson}/file', [AdminLessonController::class, 'file'])->name('lessons.file');

    Route::get('subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/{subscription}/receipt', [AdminSubscriptionController::class, 'receipt'])->name('subscriptions.receipt');
    Route::post('subscriptions/{subscription}/approve', [AdminSubscriptionController::class, 'approve'])->name('subscriptions.approve');
    Route::post('subscriptions/{subscription}/reject', [AdminSubscriptionController::class, 'reject'])->name('subscriptions.reject');

    Route::get('students', [AdminStudentController::class, 'index'])->name('students.index');
});
