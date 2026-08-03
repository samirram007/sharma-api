<?php

namespace Modules\StockItemSerial\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockItemSerial\Contracts\StockItemSerialRepositoryInterface;
use Modules\StockItemSerial\Contracts\StockItemSerialServiceInterface;
use Modules\StockItemSerial\Repositories\StockItemSerialRepository;
use Modules\StockItemSerial\Services\StockItemSerialService;

class StockItemSerialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemSerialServiceInterface::class, StockItemSerialService::class);
        $this->app->singleton(StockItemSerialRepositoryInterface::class, StockItemSerialRepository::class);
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
