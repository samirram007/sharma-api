<?php

namespace Modules\UserFiscalYear\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Services\UserFiscalYearService;

class UserFiscalYearServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserFiscalYearServiceInterface::class, UserFiscalYearService::class);
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
            ->group(__DIR__ . '/../Routes/api.php');
    }

    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
