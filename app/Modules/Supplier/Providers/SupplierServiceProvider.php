<?php

namespace Modules\Supplier\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Contracts\SupplierRepositoryInterface;
use Modules\Supplier\Contracts\SupplierServiceInterface;
use Modules\Supplier\Repositories\SupplierRepository;
use Modules\Supplier\Services\SupplierService;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(SupplierServiceInterface::class, SupplierService::class);
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
