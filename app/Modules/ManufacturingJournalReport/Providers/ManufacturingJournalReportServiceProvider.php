<?php

namespace Modules\ManufacturingJournalReport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ManufacturingJournalReport\Contracts\ManufacturingJournalReportRepositoryInterface;
use Modules\ManufacturingJournalReport\Contracts\ManufacturingJournalReportServiceInterface;
use Modules\ManufacturingJournalReport\Repositories\ManufacturingJournalReportRepository;
use Modules\ManufacturingJournalReport\Services\ManufacturingJournalReportService;

class ManufacturingJournalReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ManufacturingJournalReportRepositoryInterface::class, ManufacturingJournalReportRepository::class);
        $this->app->bind(ManufacturingJournalReportServiceInterface::class, ManufacturingJournalReportService::class);
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
