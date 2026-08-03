<?php

namespace Modules\SalaryComponent\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\SalaryComponent\Contracts\SalaryComponentRepositoryInterface;
use Modules\SalaryComponent\Contracts\SalaryComponentServiceInterface;
use Modules\SalaryComponent\Repositories\SalaryComponentRepository;
use Modules\SalaryComponent\Services\SalaryComponentService;

class SalaryComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalaryComponentServiceInterface::class, SalaryComponentService::class);
        $this->app->singleton(SalaryComponentRepositoryInterface::class, SalaryComponentRepository::class);
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
