<?php

namespace App\Providers;

use App\Models\ChatMessage;
use App\Models\Lead;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
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

        // Admin yon panelidagi "kutilayotgan cheklar", "yangi murojaatlar" va "o'qilmagan chat xabarlari" soni
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

            $unreadChatCount = 0;
            if ($user) {
                $lastSeenId = (int) Cache::get('chat_last_seen_id:'.$user->id, 0);
                $unreadChatCount = ChatMessage::where('id', '>', $lastSeenId)
                    ->where('user_id', '!=', $user->id)
                    ->when($isTeacher, fn ($q) => $q->where(function ($sub) use ($user) {
                        $sub->whereNull('course_id')
                            ->orWhereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id));
                    }))
                    ->count();
            }

            $view->with([
                'pendingCount' => $pendingCount,
                'newLeadsCount' => $newLeadsCount,
                'unreadChatCount' => $unreadChatCount,
            ]);
        });

        View::composer('partials.navbar', function ($view) {
            $user = auth()->user();
            $unreadChatCount = 0;

            if ($user && $user->hasActiveSubscription()) {
                $lastSeenId = (int) Cache::get('chat_last_seen_id:'.$user->id, 0);
                $unreadChatCount = ChatMessage::where('id', '>', $lastSeenId)
                    ->where('user_id', '!=', $user->id)
                    ->when(! $user->isAdmin(), function ($q) use ($user) {
                        $subscribedCourseIds = $user->subscriptions()
                            ->where('status', 'approved')
                            ->where(fn ($sq) => $sq->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                            ->pluck('course_id');

                        $q->where(function ($sub) use ($subscribedCourseIds) {
                            $sub->whereNull('course_id')
                                ->orWhereIn('course_id', $subscribedCourseIds);
                        });
                    })
                    ->count();
            }

            $view->with('unreadChatCount', $unreadChatCount);
        });
    }
}
