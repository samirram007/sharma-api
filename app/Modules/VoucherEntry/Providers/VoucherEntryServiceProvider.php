<?php

namespace Modules\VoucherEntry\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherEntry\Contracts\VoucherEntryRepositoryInterface;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherEntry\Repositories\VoucherEntryRepository;
use Modules\VoucherEntry\Services\VoucherEntryService;

class VoucherEntryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherEntryServiceInterface::class, VoucherEntryService::class);
        $this->app->singleton(VoucherEntryRepositoryInterface::class, VoucherEntryRepository::class);
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
