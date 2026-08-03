<?php

namespace Modules\StockJournalEntryPurge\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeRepositoryInterface;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeServiceInterface;
use Modules\StockJournalEntryPurge\Repositories\StockJournalEntryPurgeRepository;
use Modules\StockJournalEntryPurge\Services\StockJournalEntryPurgeService;

class StockJournalEntryPurgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockJournalEntryPurgeServiceInterface::class, StockJournalEntryPurgeService::class);
        $this->app->singleton(StockJournalEntryPurgeRepositoryInterface::class, StockJournalEntryPurgeRepository::class);
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
