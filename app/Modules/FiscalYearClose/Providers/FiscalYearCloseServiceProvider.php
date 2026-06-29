<?php

namespace App\Modules\FiscalYearClose\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\FiscalYearClose\Contracts\FiscalYearCloseServiceInterface;
use App\Modules\FiscalYearClose\Services\FiscalYearCloseService;

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
