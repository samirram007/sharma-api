<?php

namespace Modules\StockJournalBatchEntry\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryRepositoryInterface;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryServiceInterface;
use Modules\StockJournalBatchEntry\Repositories\StockJournalBatchEntryRepository;
use Modules\StockJournalBatchEntry\Services\StockJournalBatchEntryService;

class StockJournalBatchEntryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockJournalBatchEntryServiceInterface::class, StockJournalBatchEntryService::class);
        $this->app->singleton(StockJournalBatchEntryRepositoryInterface::class, StockJournalBatchEntryRepository::class);
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
