<?php

namespace Modules\AccountGroup\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\AccountGroup\Contracts\AccountGroupRepositoryInterface;
use Modules\AccountGroup\Contracts\AccountGroupServiceInterface;
use Modules\AccountGroup\Repositories\AccountGroupRepository;
use Modules\AccountGroup\Services\AccountGroupService;

class AccountGroupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountGroupRepositoryInterface::class, AccountGroupRepository::class);
        $this->app->singleton(AccountGroupServiceInterface::class, AccountGroupService::class);
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
