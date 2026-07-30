<?php

namespace Modules\VoucherCategory\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\VoucherCategory\Contracts\VoucherCategoryServiceInterface;
use Modules\VoucherCategory\Services\VoucherCategoryService;

class VoucherCategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VoucherCategoryServiceInterface::class, VoucherCategoryService::class);
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
