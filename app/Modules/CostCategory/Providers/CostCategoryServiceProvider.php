<?php

namespace Modules\CostCategory\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\CostCategory\Contracts\CostCategoryRepositoryInterface;
use Modules\CostCategory\Contracts\CostCategoryServiceInterface;
use Modules\CostCategory\Repositories\CostCategoryRepository;
use Modules\CostCategory\Services\CostCategoryService;

class CostCategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CostCategoryServiceInterface::class, CostCategoryService::class);
        $this->app->singleton(CostCategoryRepositoryInterface::class, CostCategoryRepository::class);
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
