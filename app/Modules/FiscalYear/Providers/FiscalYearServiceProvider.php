<?php

namespace Modules\FiscalYear\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\FiscalYear\Contracts\FiscalYearRepositoryInterface;
use Modules\FiscalYear\Contracts\FiscalYearServiceInterface;
use Modules\FiscalYear\Repositories\FiscalYearRepository;
use Modules\FiscalYear\Services\FiscalYearService;

class FiscalYearServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FiscalYearServiceInterface::class, FiscalYearService::class);
        $this->app->singleton(FiscalYearRepositoryInterface::class, FiscalYearRepository::class);
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
