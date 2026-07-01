<?php

namespace Modules\ReceiptNoteReport\Providers;

use Modules\ReceiptNoteReport\Services\ReceiptNoteReportService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReceiptNoteReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReceiptNoteReportService::class, function ($app) {
            return new ReceiptNoteReportService(
                $app->make(\Modules\Voucher\Contracts\VoucherServiceInterface::class)
            );
        });
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
