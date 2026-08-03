<?php

namespace Modules\CostCenter\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\CostCenter\Contracts\CostCenterRepositoryInterface;
use Modules\CostCenter\Contracts\CostCenterServiceInterface;
use Modules\CostCenter\Repositories\CostCenterRepository;
use Modules\CostCenter\Services\CostCenterService;

class CostCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CostCenterServiceInterface::class, CostCenterService::class);
        $this->app->singleton(CostCenterRepositoryInterface::class, CostCenterRepository::class);
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
