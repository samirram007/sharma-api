<?php

namespace Modules\Dashboard\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Dashboard\Contracts\DashboardServiceInterface;
use Modules\Dashboard\Services\DashboardService;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DashboardServiceInterface::class, DashboardService::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
