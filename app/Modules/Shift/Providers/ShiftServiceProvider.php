<?php

namespace Modules\Shift\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Shift\Contracts\ShiftRepositoryInterface;
use Modules\Shift\Contracts\ShiftServiceInterface;
use Modules\Shift\Repositories\ShiftRepository;
use Modules\Shift\Services\ShiftService;

class ShiftServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShiftServiceInterface::class, ShiftService::class);
        $this->app->singleton(ShiftRepositoryInterface::class, ShiftRepository::class);
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
