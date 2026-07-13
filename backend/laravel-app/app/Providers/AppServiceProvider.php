<?php

namespace App\Providers;

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
        // Detect Vercel and handle read-only storage constraints
        if (env('VIEW_COMPILED_PATH')) {
            $path = env('VIEW_COMPILED_PATH');
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
}
