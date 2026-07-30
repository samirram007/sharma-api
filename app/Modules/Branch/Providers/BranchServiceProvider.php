<?php

namespace Modules\Branch\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Branch\Contracts\BranchRepositoryInterface;
use Modules\Branch\Contracts\BranchServiceInterface;
use Modules\Branch\Repositories\BranchRepository;
use Modules\Branch\Services\BranchService;

class BranchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BranchServiceInterface::class, BranchService::class);
        $this->app->singleton(BranchRepositoryInterface::class, BranchRepository::class);
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
