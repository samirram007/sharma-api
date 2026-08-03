<?php

namespace Modules\VoucherPaymentMode\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeRepositoryInterface;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeServiceInterface;
use Modules\VoucherPaymentMode\Repositories\VoucherPaymentModeRepository;
use Modules\VoucherPaymentMode\Services\VoucherPaymentModeService;

class VoucherPaymentModeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherPaymentModeServiceInterface::class, VoucherPaymentModeService::class);
        $this->app->singleton(VoucherPaymentModeRepositoryInterface::class, VoucherPaymentModeRepository::class);
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
