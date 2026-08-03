<?php

namespace Modules\EmployeeGroup\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\EmployeeGroup\Contracts\EmployeeGroupRepositoryInterface;
use Modules\EmployeeGroup\Contracts\EmployeeGroupServiceInterface;
use Modules\EmployeeGroup\Repositories\EmployeeGroupRepository;
use Modules\EmployeeGroup\Services\EmployeeGroupService;

class EmployeeGroupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeGroupServiceInterface::class, EmployeeGroupService::class);
        $this->app->singleton(EmployeeGroupRepositoryInterface::class, EmployeeGroupRepository::class);
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
