<?php

namespace Modules\AccountNature\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\AccountNature\Contracts\AccountNatureRepositoryInterface;
use Modules\AccountNature\Contracts\AccountNatureServiceInterface;
use Modules\AccountNature\Repositories\AccountNatureRepository;
use Modules\AccountNature\Services\AccountNatureService;

class AccountNatureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountNatureServiceInterface::class, AccountNatureService::class);
        $this->app->singleton(AccountNatureRepositoryInterface::class, AccountNatureRepository::class);
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
