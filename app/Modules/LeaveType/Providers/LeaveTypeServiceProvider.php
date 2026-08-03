<?php

namespace Modules\LeaveType\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\LeaveType\Contracts\LeaveTypeRepositoryInterface;
use Modules\LeaveType\Contracts\LeaveTypeServiceInterface;
use Modules\LeaveType\Repositories\LeaveTypeRepository;
use Modules\LeaveType\Services\LeaveTypeService;

class LeaveTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeaveTypeServiceInterface::class, LeaveTypeService::class);
        $this->app->singleton(LeaveTypeRepositoryInterface::class, LeaveTypeRepository::class);
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
