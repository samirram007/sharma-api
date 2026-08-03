<?php

namespace Modules\UniqueQuantityCode\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeRepositoryInterface;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeServiceInterface;
use Modules\UniqueQuantityCode\Repositories\UniqueQuantityCodeRepository;
use Modules\UniqueQuantityCode\Services\UniqueQuantityCodeService;

class UniqueQuantityCodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UniqueQuantityCodeServiceInterface::class, UniqueQuantityCodeService::class);
        $this->app->singleton(UniqueQuantityCodeRepositoryInterface::class, UniqueQuantityCodeRepository::class);
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
