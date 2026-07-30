<?php

namespace Modules\DeliveryRoute\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\DeliveryRoute\Contracts\DeliveryRouteServiceInterface;
use Modules\DeliveryRoute\Services\DeliveryRouteService;

class DeliveryRouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DeliveryRouteServiceInterface::class, DeliveryRouteService::class);
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
