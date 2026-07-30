<?php

namespace Modules\StockUnit\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockUnit\Contracts\StockUnitRepositoryInterface;
use Modules\StockUnit\Contracts\StockUnitServiceInterface;
use Modules\StockUnit\Repositories\StockUnitRepository;
use Modules\StockUnit\Services\StockUnitService;

class StockUnitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockUnitServiceInterface::class, StockUnitService::class);
        $this->app->singleton(StockUnitRepositoryInterface::class, StockUnitRepository::class);
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
