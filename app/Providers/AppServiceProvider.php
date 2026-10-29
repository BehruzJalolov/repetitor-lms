<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\Subscription;
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
        // Admin yon panelidagi "kutilayotgan cheklar" va "yangi murojaatlar" soni
        View::composer('layouts.admin', function ($view) {
            $view->with([
                'pendingCount' => Subscription::where('status', 'pending')->count(),
                'newLeadsCount' => Lead::where('status', 'new')->count(),
            ]);
        });
    }
}
