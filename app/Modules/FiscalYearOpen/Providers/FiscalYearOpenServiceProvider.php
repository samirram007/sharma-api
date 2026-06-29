<?php

namespace App\Modules\FiscalYearOpen\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\FiscalYearOpen\Contracts\FiscalYearOpenServiceInterface;
use App\Modules\FiscalYearOpen\Services\FiscalYearOpenService;

class FiscalYearOpenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FiscalYearOpenServiceInterface::class, FiscalYearOpenService::class);
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
