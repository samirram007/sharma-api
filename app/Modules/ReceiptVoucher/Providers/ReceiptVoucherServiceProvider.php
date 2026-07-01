<?php

namespace Modules\ReceiptVoucher\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\ReceiptVoucher\Contracts\ReceiptVoucherServiceInterface;
use Modules\ReceiptVoucher\Services\ReceiptVoucherService;

class ReceiptVoucherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReceiptVoucherServiceInterface::class, ReceiptVoucherService::class);
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
