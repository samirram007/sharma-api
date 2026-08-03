<?php

namespace Modules\VoucherDispatchDetail\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailRepositoryInterface;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Repositories\VoucherDispatchDetailRepository;
use Modules\VoucherDispatchDetail\Services\VoucherDispatchDetailService;

class VoucherDispatchDetailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherDispatchDetailServiceInterface::class, VoucherDispatchDetailService::class);
        $this->app->singleton(VoucherDispatchDetailRepositoryInterface::class, VoucherDispatchDetailRepository::class);
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
