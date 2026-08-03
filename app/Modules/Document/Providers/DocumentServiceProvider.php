<?php

namespace Modules\Document\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Document\Contracts\DocumentRepositoryInterface;
use Modules\Document\Contracts\DocumentServiceInterface;
use Modules\Document\Repositories\DocumentRepository;
use Modules\Document\Services\DocumentService;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
        $this->app->singleton(DocumentRepositoryInterface::class, DocumentRepository::class);
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
        // $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
