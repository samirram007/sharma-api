<?php

namespace Modules\VoucherEntryPurge\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeServiceInterface;
use Modules\VoucherEntryPurge\Services\VoucherEntryPurgeService;

class VoucherEntryPurgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherEntryPurgeServiceInterface::class, VoucherEntryPurgeService::class);
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
