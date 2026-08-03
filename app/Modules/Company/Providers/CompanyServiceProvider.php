<?php

namespace Modules\Company\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Company\Contracts\CompanyRepositoryInterface;
use Modules\Company\Contracts\CompanyServiceInterface;
use Modules\Company\Repositories\CompanyRepository;
use Modules\Company\Services\CompanyService;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->singleton(CompanyServiceInterface::class, CompanyService::class);
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
