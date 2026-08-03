<?php

namespace Modules\VoucherParty\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherParty\Contracts\VoucherPartyRepositoryInterface;
use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;
use Modules\VoucherParty\Repositories\VoucherPartyRepository;
use Modules\VoucherParty\Services\VoucherPartyService;

class VoucherPartyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherPartyServiceInterface::class, VoucherPartyService::class);
        $this->app->singleton(VoucherPartyRepositoryInterface::class, VoucherPartyRepository::class);
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
