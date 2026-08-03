<?php

namespace Modules\StockJournalGodownEntryPurge\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeRepositoryInterface;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeServiceInterface;
use Modules\StockJournalGodownEntryPurge\Repositories\StockJournalGodownEntryPurgeRepository;
use Modules\StockJournalGodownEntryPurge\Services\StockJournalGodownEntryPurgeService;

class StockJournalGodownEntryPurgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockJournalGodownEntryPurgeServiceInterface::class, StockJournalGodownEntryPurgeService::class);
        $this->app->singleton(StockJournalGodownEntryPurgeRepositoryInterface::class, StockJournalGodownEntryPurgeRepository::class);
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
