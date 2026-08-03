<?php

namespace Modules\GstRegistrationType\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeRepositoryInterface;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeServiceInterface;
use Modules\GstRegistrationType\Repositories\GstRegistrationTypeRepository;
use Modules\GstRegistrationType\Services\GstRegistrationTypeService;

class GstRegistrationTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GstRegistrationTypeServiceInterface::class, GstRegistrationTypeService::class);
        $this->app->singleton(GstRegistrationTypeRepositoryInterface::class, GstRegistrationTypeRepository::class);
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
