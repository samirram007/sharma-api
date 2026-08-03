<?php

namespace Modules\StockItemBatch\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockItemBatch\Contracts\StockItemBatchRepositoryInterface;
use Modules\StockItemBatch\Contracts\StockItemBatchServiceInterface;
use Modules\StockItemBatch\Repositories\StockItemBatchRepository;
use Modules\StockItemBatch\Services\StockItemBatchService;

class StockItemBatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemBatchServiceInterface::class, StockItemBatchService::class);
        $this->app->singleton(StockItemBatchRepositoryInterface::class, StockItemBatchRepository::class);
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
