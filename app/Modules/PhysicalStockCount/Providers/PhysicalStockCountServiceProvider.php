<?php

namespace Modules\PhysicalStockCount\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\PhysicalStockCount\Contracts\PhysicalStockCountServiceInterface;
use Modules\PhysicalStockCount\Services\PhysicalStockCountService;

class PhysicalStockCountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PhysicalStockCountServiceInterface::class, PhysicalStockCountService::class);
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
