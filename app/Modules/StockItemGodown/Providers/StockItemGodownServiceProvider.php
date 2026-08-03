<?php

namespace Modules\StockItemGodown\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockItemGodown\Contracts\StockItemGodownRepositoryInterface;
use Modules\StockItemGodown\Contracts\StockItemGodownServiceInterface;
use Modules\StockItemGodown\Repositories\StockItemGodownRepository;
use Modules\StockItemGodown\Services\StockItemGodownService;

class StockItemGodownServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemGodownServiceInterface::class, StockItemGodownService::class);
        $this->app->singleton(StockItemGodownRepositoryInterface::class, StockItemGodownRepository::class);
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
