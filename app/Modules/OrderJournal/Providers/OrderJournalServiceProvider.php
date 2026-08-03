<?php

namespace Modules\OrderJournal\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\OrderJournal\Contracts\OrderJournalRepositoryInterface;
use Modules\OrderJournal\Contracts\OrderJournalServiceInterface;
use Modules\OrderJournal\Repositories\OrderJournalRepository;
use Modules\OrderJournal\Services\OrderJournalService;

class OrderJournalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderJournalServiceInterface::class, OrderJournalService::class);
        $this->app->singleton(OrderJournalRepositoryInterface::class, OrderJournalRepository::class);
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
