<?php

namespace Modules\Journal\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Journal\Contracts\JournalRepositoryInterface;
use Modules\Journal\Contracts\JournalServiceInterface;
use Modules\Journal\Repositories\JournalRepository;
use Modules\Journal\Services\JournalService;

class JournalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(JournalServiceInterface::class, JournalService::class);
        $this->app->singleton(JournalRepositoryInterface::class, JournalRepository::class);
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
