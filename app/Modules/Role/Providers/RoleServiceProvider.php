<?php

namespace Modules\Role\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Role\Contracts\RoleRepositoryInterface;
use Modules\Role\Contracts\RoleServiceInterface;
use Modules\Role\Repositories\RoleRepository;
use Modules\Role\Services\RoleService;

class RoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->singleton(RoleRepositoryInterface::class, RoleRepository::class);
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
