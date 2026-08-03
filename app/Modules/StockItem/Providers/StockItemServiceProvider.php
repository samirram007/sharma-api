<?php

namespace Modules\StockItem\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockItem\Contracts\StockItemRepositoryInterface;
use Modules\StockItem\Contracts\StockItemServiceInterface;
use Modules\StockItem\Repositories\StockItemRepository;
use Modules\StockItem\Services\StockItemService;

class StockItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockItemServiceInterface::class, StockItemService::class);
        $this->app->singleton(StockItemRepositoryInterface::class, StockItemRepository::class);
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
