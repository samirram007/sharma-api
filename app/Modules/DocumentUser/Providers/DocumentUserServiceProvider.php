<?php

namespace Modules\DocumentUser\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\DocumentUser\Contracts\DocumentUserRepositoryInterface;
use Modules\DocumentUser\Contracts\DocumentUserServiceInterface;
use Modules\DocumentUser\Repositories\DocumentUserRepository;
use Modules\DocumentUser\Services\DocumentUserService;

class DocumentUserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentUserServiceInterface::class, DocumentUserService::class);
        $this->app->singleton(DocumentUserRepositoryInterface::class, DocumentUserRepository::class);
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
