<?php

namespace Modules\AccountLedger\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\AccountLedger\Contracts\AccountLedgerServiceInterface;
use Modules\AccountLedger\Services\AccountLedgerService;

class AccountLedgerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountLedgerServiceInterface::class, AccountLedgerService::class);
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
