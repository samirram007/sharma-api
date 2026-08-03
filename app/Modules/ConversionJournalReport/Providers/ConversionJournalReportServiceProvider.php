<?php

namespace Modules\ConversionJournalReport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ConversionJournalReport\Contracts\ConversionJournalReportRepositoryInterface;
use Modules\ConversionJournalReport\Contracts\ConversionJournalReportServiceInterface;
use Modules\ConversionJournalReport\Repositories\ConversionJournalReportRepository;
use Modules\ConversionJournalReport\Services\ConversionJournalReportService;

class ConversionJournalReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConversionJournalReportRepositoryInterface::class, ConversionJournalReportRepository::class);
        $this->app->bind(ConversionJournalReportServiceInterface::class, ConversionJournalReportService::class);
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
