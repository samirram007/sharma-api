<?php

namespace Modules\Country\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Country\Contracts\CountryRepositoryInterface;
use Modules\Country\Contracts\CountryServiceInterface;
use Modules\Country\Repositories\CountryRepository;
use Modules\Country\Services\CountryService;

class CountryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CountryServiceInterface::class, CountryService::class);
        $this->app->singleton(CountryRepositoryInterface::class, CountryRepository::class);
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
