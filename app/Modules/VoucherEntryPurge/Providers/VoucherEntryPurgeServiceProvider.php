<?php

namespace Modules\VoucherEntryPurge\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeRepositoryInterface;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeServiceInterface;
use Modules\VoucherEntryPurge\Repositories\VoucherEntryPurgeRepository;
use Modules\VoucherEntryPurge\Services\VoucherEntryPurgeService;

class VoucherEntryPurgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherEntryPurgeServiceInterface::class, VoucherEntryPurgeService::class);
        $this->app->singleton(VoucherEntryPurgeRepositoryInterface::class, VoucherEntryPurgeRepository::class);
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
