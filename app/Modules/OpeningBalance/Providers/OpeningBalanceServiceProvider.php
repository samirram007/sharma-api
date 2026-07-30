<?php

namespace Modules\OpeningBalance\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\OpeningBalance\Contracts\OpeningBalanceServiceInterface;
use Modules\OpeningBalance\Services\OpeningBalanceService;

class OpeningBalanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OpeningBalanceServiceInterface::class, OpeningBalanceService::class);
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
