<?php

namespace Modules\OrderStockJournal\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\OrderStockJournal\Contracts\OrderStockJournalRepositoryInterface;
use Modules\OrderStockJournal\Contracts\OrderStockJournalServiceInterface;
use Modules\OrderStockJournal\Repositories\OrderStockJournalRepository;
use Modules\OrderStockJournal\Services\OrderStockJournalService;

class OrderStockJournalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderStockJournalServiceInterface::class, OrderStockJournalService::class);
        $this->app->singleton(OrderStockJournalRepositoryInterface::class, OrderStockJournalRepository::class);
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
