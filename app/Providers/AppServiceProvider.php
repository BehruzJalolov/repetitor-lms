<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\Subscription;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Admin yon panelidagi "kutilayotgan cheklar" va "yangi murojaatlar" soni (Ustoz uchun faqat o'z kurslari bo'yicha)
        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();
            $isTeacher = $user && $user->isTeacher();

            $pendingCount = Subscription::where('status', 'pending')
                ->when($isTeacher, fn ($q) => $q->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id)))
                ->count();

            $newLeadsCount = Lead::where('status', 'new')
                ->when($isTeacher, fn ($q) => $q->where(function ($sub) use ($user) {
                    $sub->whereNull('course_id')
                        ->orWhereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id));
                }))
                ->count();

            $view->with([
                'pendingCount' => $pendingCount,
                'newLeadsCount' => $newLeadsCount,
            ]);
        });
    }
}
