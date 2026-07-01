<?php

namespace Modules\FiscalYearClose\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\FiscalYearClose\Contracts\FiscalYearCloseServiceInterface;
use Modules\FiscalYearClose\Services\FiscalYearCloseService;

class FiscalYearCloseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FiscalYearCloseServiceInterface::class, FiscalYearCloseService::class);
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
