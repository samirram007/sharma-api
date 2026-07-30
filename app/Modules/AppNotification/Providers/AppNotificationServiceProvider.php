<?php

namespace Modules\AppNotification\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\AppNotification\Contracts\AppNotificationServiceInterface;
use Modules\AppNotification\Services\AppNotificationService;

class AppNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AppNotificationServiceInterface::class, AppNotificationService::class);
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
