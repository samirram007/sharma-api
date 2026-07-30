<?php

namespace Modules\StockCategory\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockCategory\Contracts\StockCategoryRepositoryInterface;
use Modules\StockCategory\Contracts\StockCategoryServiceInterface;
use Modules\StockCategory\Repositories\StockCategoryRepository;
use Modules\StockCategory\Services\StockCategoryService;

class StockCategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockCategoryServiceInterface::class, StockCategoryService::class);
        $this->app->singleton(StockCategoryRepositoryInterface::class, StockCategoryRepository::class);
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
