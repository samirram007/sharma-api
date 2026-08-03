<?php

namespace Modules\Language\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Language\Contracts\LanguageRepositoryInterface;
use Modules\Language\Contracts\LanguageServiceInterface;
use Modules\Language\Repositories\LanguageRepository;
use Modules\Language\Services\LanguageService;

class LanguageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LanguageServiceInterface::class, LanguageService::class);
        $this->app->singleton(LanguageRepositoryInterface::class, LanguageRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadMigrations();
    }

    private function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../Routes/api.php');
    }

    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
