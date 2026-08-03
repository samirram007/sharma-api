<?php

namespace Modules\Setting\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Setting\Contracts\SettingRepositoryInterface;
use Modules\Setting\Contracts\SettingServiceInterface;
use Modules\Setting\Repositories\SettingRepository;
use Modules\Setting\Services\SettingService;

class SettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
        $this->app->singleton(SettingRepositoryInterface::class, SettingRepository::class);
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
