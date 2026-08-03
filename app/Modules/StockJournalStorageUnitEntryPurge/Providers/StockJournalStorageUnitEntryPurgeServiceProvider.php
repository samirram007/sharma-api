<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeRepositoryInterface;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeServiceInterface;
use Modules\StockJournalStorageUnitEntryPurge\Repositories\StockJournalStorageUnitEntryPurgeRepository;
use Modules\StockJournalStorageUnitEntryPurge\Services\StockJournalStorageUnitEntryPurgeService;

class StockJournalStorageUnitEntryPurgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockJournalStorageUnitEntryPurgeServiceInterface::class, StockJournalStorageUnitEntryPurgeService::class);
        $this->app->singleton(StockJournalStorageUnitEntryPurgeRepositoryInterface::class, StockJournalStorageUnitEntryPurgeRepository::class);
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
