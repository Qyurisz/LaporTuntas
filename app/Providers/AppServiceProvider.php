<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('create-report', function (Request $request): Limit {
            return Limit::perMinutes(10, 10)->by($request->ip());
        });

        RateLimiter::for('track-ticket', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
