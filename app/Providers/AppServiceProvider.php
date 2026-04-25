<?php

namespace App\Providers;

use App\Services\AccessDatabaseService;
use App\Services\AccessDatabaseServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind interface → implementation (testability-first)
        $this->app->bind(
            AccessDatabaseServiceInterface::class,
            AccessDatabaseService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
