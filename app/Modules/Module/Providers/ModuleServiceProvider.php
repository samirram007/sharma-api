<?php

namespace Modules\Module\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Module\Contracts\ModuleRepositoryInterface;
use Modules\Module\Contracts\ModuleServiceInterface;
use Modules\Module\Repositories\ModuleRepository;
use Modules\Module\Services\ModuleService;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ModuleServiceInterface::class, ModuleService::class);
        $this->app->singleton(ModuleRepositoryInterface::class, ModuleRepository::class);
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
