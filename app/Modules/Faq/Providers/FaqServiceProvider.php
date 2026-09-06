<?php

namespace Modules\Faq\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Faq\Contracts\FaqRepositoryInterface;
use Modules\Faq\Contracts\FaqServiceInterface;
use Modules\Faq\Repositories\FaqRepository;
use Modules\Faq\Services\FaqService;

class FaqServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FaqServiceInterface::class, FaqService::class);
        $this->app->singleton(FaqRepositoryInterface::class, FaqRepository::class);
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
