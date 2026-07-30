<?php

namespace Modules\RolePermission\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\RolePermission\Contracts\RolePermissionServiceInterface;
use Modules\RolePermission\Services\RolePermissionService;

class RolePermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RolePermissionServiceInterface::class, RolePermissionService::class);
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
