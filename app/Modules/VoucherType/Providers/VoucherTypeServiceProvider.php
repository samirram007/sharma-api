<?php

namespace Modules\VoucherType\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherType\Contracts\VoucherTypeRepositoryInterface;
use Modules\VoucherType\Contracts\VoucherTypeServiceInterface;
use Modules\VoucherType\Repositories\VoucherTypeRepository;
use Modules\VoucherType\Services\VoucherTypeService;

class VoucherTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherTypeServiceInterface::class, VoucherTypeService::class);
        $this->app->singleton(VoucherTypeRepositoryInterface::class, VoucherTypeRepository::class);
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
