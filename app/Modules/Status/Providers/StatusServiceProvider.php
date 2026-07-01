<?php

namespace Modules\Status\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Status\Contracts\StatusServiceInterface;
use Modules\Status\Services\StatusService;

class StatusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StatusServiceInterface::class, StatusService::class);
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
            ->group(__DIR__ . '/../Routes/api.php');
    }

    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
