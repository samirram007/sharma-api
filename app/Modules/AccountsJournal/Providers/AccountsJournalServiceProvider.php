<?php

namespace Modules\AccountsJournal\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\AccountsJournal\Contracts\AccountsJournalRepositoryInterface;
use Modules\AccountsJournal\Contracts\AccountsJournalServiceInterface;
use Modules\AccountsJournal\Repositories\AccountsJournalRepository;
use Modules\AccountsJournal\Services\AccountsJournalService;

class AccountsJournalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountsJournalServiceInterface::class, AccountsJournalService::class);
        $this->app->singleton(AccountsJournalRepositoryInterface::class, AccountsJournalRepository::class);
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
