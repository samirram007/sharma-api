<?php

namespace Modules\State\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\State\Contracts\StateServiceInterface;
use Modules\State\Services\StateService;

class StateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StateServiceInterface::class, StateService::class);
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
