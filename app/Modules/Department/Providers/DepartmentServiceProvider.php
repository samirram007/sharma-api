<?php

namespace Modules\Department\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Department\Contracts\DepartmentRepositoryInterface;
use Modules\Department\Contracts\DepartmentServiceInterface;
use Modules\Department\Repositories\DepartmentRepository;
use Modules\Department\Services\DepartmentService;

class DepartmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DepartmentServiceInterface::class, DepartmentService::class);
        $this->app->singleton(DepartmentRepositoryInterface::class, DepartmentRepository::class);
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
