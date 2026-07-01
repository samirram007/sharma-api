<?php

namespace Modules\Agent\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Agent\Contracts\AgentServiceInterface;
use Modules\Agent\Services\AgentService;

class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AgentServiceInterface::class, AgentService::class);
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
            ->group(__DIR__ . '/../Routes/api.php');
    }

    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
