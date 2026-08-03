<?php

namespace Modules\Designation\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Designation\Contracts\DesignationRepositoryInterface;
use Modules\Designation\Contracts\DesignationServiceInterface;
use Modules\Designation\Repositories\DesignationRepository;
use Modules\Designation\Services\DesignationService;

class DesignationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DesignationServiceInterface::class, DesignationService::class);
        $this->app->singleton(DesignationRepositoryInterface::class, DesignationRepository::class);
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
