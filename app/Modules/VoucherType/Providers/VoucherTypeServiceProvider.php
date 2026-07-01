<?php

namespace Modules\VoucherType\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\VoucherType\Contracts\VoucherTypeServiceInterface;
use Modules\VoucherType\Services\VoucherTypeService;

class VoucherTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherTypeServiceInterface::class, VoucherTypeService::class);
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
