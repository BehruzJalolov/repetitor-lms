<?php

use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StreamController as AdminStreamController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MyCoursesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

// Ochiq sahifalar
Route::get('/', HomeController::class)->name('home');
Route::get('/kurslar', [CourseController::class, 'index'])->name('courses.index');
Route::get('/kurslar/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/ustozlar', [TeacherController::class, 'index'])->name('teachers.index');
Route::get('/ustozlar/{teacher:slug}', [TeacherController::class, 'show'])->name('teachers.show');
Route::post('/murojaat', [LeadController::class, 'store'])->middleware('throttle:5,1')->name('leads.store');

// O'quvchi (tizimga kirgan foydalanuvchi)
Route::middleware('auth')->group(function () {
    Route::get('/mening-kurslarim', MyCoursesController::class)->name('my-courses');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->middleware('throttle:5,1')->name('profile.update');
    Route::post('/profil/email-tasdiqlash', [ProfileController::class, 'verifyEmail'])->middleware('throttle:5,1')->name('profile.email.verify');
    Route::post('/profil/parol-kodi', [ProfileController::class, 'sendPasswordCode'])->middleware('throttle:3,1')->name('profile.password.code');
    Route::put('/profil/parolni-yangilash', [ProfileController::class, 'updatePassword'])->middleware('throttle:5,1')->name('profile.password.update');

    Route::get('/kurslar/{course:slug}/darslar/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('/kurslar/{course:slug}/darslar/{lesson}/tugatish', [LessonController::class, 'complete'])->middleware('throttle:20,1')->name('lessons.complete');
    Route::post('/kurslar/{course:slug}/darslar/{lesson}/test', [LessonController::class, 'submitQuiz'])->middleware('throttle:15,1')->name('lessons.quiz');
    Route::get('/kurslar/{course:slug}/darslar/{lesson}/video', [LessonController::class, 'video'])->name('lessons.video');
    Route::get('/kurslar/{course:slug}/darslar/{lesson}/fayl', [LessonController::class, 'file'])->name('lessons.file');
    Route::get('/kurslar/{course:slug}/sertifikat', [CertificateController::class, 'show'])->name('courses.certificate');

    // Jonli efirlar (Stream)
    Route::get('/jonli-efir', [StreamController::class, 'index'])->name('streams.index');
    Route::get('/jonli-efir/{stream}', [StreamController::class, 'show'])->name('streams.show');

    // O'quvchilar va Ustoz muloqot chati
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/xabarlar', [ChatController::class, 'messages'])->middleware('throttle:60,1')->name('chat.messages');
    Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:20,1')->name('chat.store');
    Route::delete('/chat/{chatMessage}', [ChatController::class, 'destroy'])->middleware('throttle:30,1')->name('chat.destroy');
    Route::post('/chat/{chatMessage}/pin', [ChatController::class, 'togglePin'])->middleware('throttle:30,1')->name('chat.pin');
    Route::post('/chat/foydalanuvchi/{user}/bloklash', [ChatController::class, 'toggleBan'])->middleware('throttle:30,1')->name('chat.ban');

    Route::post('/kurslar/{course:slug}/tolov', [SubscriptionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('subscriptions.store');
});

// Autentifikatsiya
Route::middleware('guest')->group(function () {
    Route::get('/kirish', [LoginController::class, 'create'])->name('login');
    Route::post('/kirish', [LoginController::class, 'store'])->name('login.store');
    Route::get('/royxatdan-otish', [RegisterController::class, 'create'])->name('register');
    Route::post('/royxatdan-otish', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/royxatdan-otish/tasdiqlash', [RegisterController::class, 'showVerifyForm'])->name('register.verify');
    Route::post('/royxatdan-otish/tasdiqlash', [RegisterController::class, 'verify'])->middleware('throttle:5,1')->name('register.verify.store');
    Route::get('/royxatdan-otish/telegram-holat', [RegisterController::class, 'pollTelegramStatus'])->middleware('throttle:60,1')->name('register.telegram.poll');
    Route::post('/royxatdan-otish/telegram-tekshirish', [RegisterController::class, 'checkTelegram'])->middleware('throttle:10,1')->name('register.telegram.check');
    Route::post('/royxatdan-otish/qayta-yuborish', [RegisterController::class, 'resend'])->middleware('throttle:3,1')->name('register.resend');

    // Parolni tiklash (SMS orqali)
    Route::get('/parolni-tiklash', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/parolni-tiklash', [PasswordResetController::class, 'sendResetCode'])->middleware('throttle:3,1')->name('password.phone');
    Route::get('/parolni-tiklash/tasdiqlash', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/parolni-tiklash/tasdiqlash', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/chiqish', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Admin va Ustozlar boshqaruv paneli
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('admin:manage_courses')->group(function () {
        Route::resource('courses', AdminCourseController::class);
        Route::resource('courses.lessons', AdminLessonController::class)->shallow()->only(['create', 'store', 'edit', 'update', 'destroy']);
        Route::post('lessons/{lesson}/move/{direction}', [AdminLessonController::class, 'move'])->name('lessons.move');
        Route::get('lessons/{lesson}/file', [AdminLessonController::class, 'file'])->name('lessons.file');
        Route::post('lessons/{lesson}/questions', [AdminLessonController::class, 'storeQuestion'])->name('lessons.questions.store');
        Route::delete('lessons/{lesson}/questions/{question}', [AdminLessonController::class, 'destroyQuestion'])->name('lessons.questions.destroy');
    });

    Route::resource('teachers', AdminTeacherController::class);
    Route::post('teachers/{teacher}/results', [AdminTeacherController::class, 'storeResult'])->name('teachers.results.store');
    Route::delete('teachers/{teacher}/results/{result}', [AdminTeacherController::class, 'destroyResult'])->name('teachers.results.destroy');

    Route::middleware('admin:manage_leads')->group(function () {
        Route::get('leads', [AdminLeadController::class, 'index'])->name('leads.index');
        Route::patch('leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.status');
        Route::delete('leads/{lead}', [AdminLeadController::class, 'destroy'])->name('leads.destroy');
    });

    Route::middleware('admin:manage_subscriptions')->group(function () {
        Route::get('subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/{subscription}/receipt', [AdminSubscriptionController::class, 'receipt'])->name('subscriptions.receipt');
        Route::post('subscriptions/{subscription}/approve', [AdminSubscriptionController::class, 'approve'])->name('subscriptions.approve');
        Route::post('subscriptions/{subscription}/reject', [AdminSubscriptionController::class, 'reject'])->name('subscriptions.reject');
    });

    Route::middleware('admin:view_students')->group(function () {
        Route::get('students', [AdminStudentController::class, 'index'])->name('students.index');
    });

    Route::middleware('admin:manage_streams')->group(function () {
        Route::get('streams', [AdminStreamController::class, 'index'])->name('streams.index');
        Route::post('streams', [AdminStreamController::class, 'store'])->name('streams.store');
        Route::post('streams/{stream}/start', [AdminStreamController::class, 'start'])->name('streams.start');
        Route::post('streams/{stream}/end', [AdminStreamController::class, 'end'])->name('streams.end');
        Route::delete('streams/{stream}', [AdminStreamController::class, 'destroy'])->name('streams.destroy');
    });

    Route::middleware('admin:super')->group(function () {
        Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::post('settings/test-telegram', [AdminSettingController::class, 'testTelegram'])->name('settings.test-telegram');
    });
});
