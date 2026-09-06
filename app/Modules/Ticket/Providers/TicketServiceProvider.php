<?php

namespace Modules\Ticket\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Ticket\Contracts\TicketRepositoryInterface;
use Modules\Ticket\Contracts\TicketServiceInterface;
use Modules\Ticket\Repositories\TicketRepository;
use Modules\Ticket\Services\TicketService;

class TicketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TicketServiceInterface::class, TicketService::class);
        $this->app->singleton(TicketRepositoryInterface::class, TicketRepository::class);
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
