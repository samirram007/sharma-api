<?php

namespace Modules\ReceiptNoteReport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ReceiptNoteReport\Contracts\ReceiptNoteReportRepositoryInterface;
use Modules\ReceiptNoteReport\Contracts\ReceiptNoteReportServiceInterface;
use Modules\ReceiptNoteReport\Repositories\ReceiptNoteReportRepository;
use Modules\ReceiptNoteReport\Services\ReceiptNoteReportService;
use Modules\Voucher\Contracts\VoucherServiceInterface;

class ReceiptNoteReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReceiptNoteReportRepositoryInterface::class, ReceiptNoteReportRepository::class);
        $this->app->bind(ReceiptNoteReportServiceInterface::class, function ($app) {
            return new ReceiptNoteReportService(
                $app->make(VoucherServiceInterface::class)
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
            ->group(__DIR__.'/../Routes/api.php');
    }
}
