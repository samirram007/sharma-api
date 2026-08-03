<?php

namespace Modules\UserRole\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\UserRole\Contracts\UserRoleRepositoryInterface;
use Modules\UserRole\Contracts\UserRoleServiceInterface;
use Modules\UserRole\Repositories\UserRoleRepository;
use Modules\UserRole\Services\UserRoleService;

class UserRoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRoleServiceInterface::class, UserRoleService::class);
        $this->app->singleton(UserRoleRepositoryInterface::class, UserRoleRepository::class);
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
