<?php

namespace Modules\Employee\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Employee\Contracts\EmployeeRepositoryInterface;
use Modules\Employee\Contracts\EmployeeServiceInterface;
use Modules\Employee\Repositories\EmployeeRepository;
use Modules\Employee\Services\EmployeeService;

class EmployeeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(EmployeeServiceInterface::class, EmployeeService::class);
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
