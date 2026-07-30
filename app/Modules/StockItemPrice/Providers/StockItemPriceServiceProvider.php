<?php

namespace Modules\StockItemPrice\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockItemPrice\Contracts\StockItemPriceServiceInterface;
use Modules\StockItemPrice\Services\StockItemPriceService;

class StockItemPriceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemPriceServiceInterface::class, StockItemPriceService::class);
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
