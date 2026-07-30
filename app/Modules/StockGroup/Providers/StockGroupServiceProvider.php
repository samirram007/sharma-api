<?php

namespace Modules\StockGroup\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockGroup\Contracts\StockGroupRepositoryInterface;
use Modules\StockGroup\Contracts\StockGroupServiceInterface;
use Modules\StockGroup\Repositories\StockGroupRepository;
use Modules\StockGroup\Services\StockGroupService;

class StockGroupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockGroupServiceInterface::class, StockGroupService::class);
        $this->app->singleton(StockGroupRepositoryInterface::class, StockGroupRepository::class);
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
