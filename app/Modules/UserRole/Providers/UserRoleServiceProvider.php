<?php

namespace Modules\UserRole\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\UserRole\Contracts\UserRoleServiceInterface;
use Modules\UserRole\Services\UserRoleService;

class UserRoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRoleServiceInterface::class, UserRoleService::class);
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
