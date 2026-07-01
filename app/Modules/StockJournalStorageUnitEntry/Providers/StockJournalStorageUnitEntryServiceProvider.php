<?php

namespace Modules\StockJournalStorageUnitEntry\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\StockJournalStorageUnitEntry\Contracts\StockJournalStorageUnitEntryServiceInterface;
use Modules\StockJournalStorageUnitEntry\Services\StockJournalStorageUnitEntryService;

class StockJournalStorageUnitEntryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StockJournalStorageUnitEntryServiceInterface::class, StockJournalStorageUnitEntryService::class);
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
