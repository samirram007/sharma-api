<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModuleService extends Command
{
    protected $signature = 'make:module-service
        {name : The module name in PascalCase (e.g., StockCategory)}
        {--model= : The model class name (defaults to the module name)}
        {--force : Overwrite existing files}';

    protected $description = 'Scaffold a service class and interface extending BaseService for a module';

    public function handle(): int
    {
        $name = $this->argument('name');
        $model = $this->option('model') ?: $name;
        $force = $this->option('force');

        $modulePath = app_path("Modules/{$name}");

        // Ensure directories exist
        File::ensureDirectoryExists("{$modulePath}/Services");
        File::ensureDirectoryExists("{$modulePath}/Contracts");

        // Create interface
        $interfacePath = "{$modulePath}/Contracts/{$name}ServiceInterface.php";
        if ($this->writeFile($interfacePath, $this->buildInterface($name, $model), $force)) {
            $this->info("Created: {$interfacePath}");
        }

        // Create service
        $servicePath = "{$modulePath}/Services/{$name}Service.php";
        if ($this->writeFile($servicePath, $this->buildService($name, $model), $force)) {
            $this->info("Created: {$servicePath}");
        }

        $this->newLine();
        $this->components->info("{$name} module service scaffolded successfully.");

        return Command::SUCCESS;
    }

    protected function writeFile(string $path, string $content, bool $force): bool
    {
        if (File::exists($path) && ! $force) {
            $this->warn("Skipped (already exists): {$path}");

            return false;
        }

        File::put($path, $content);

        return true;
    }

    protected function buildInterface(string $name, string $model): string
    {
        $namespace = "Modules\\{$name}\\Contracts";
        $modelNamespace = "Modules\\{$name}\\Models\\{$model}";

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Collection;
use {$modelNamespace};

interface {$name}ServiceInterface
{
    public function getAll(): Collection;
    public function getById(int \$id): ?{$model};
    public function store(array \$data): {$model};
    public function update(array \$data, int \$id): {$model};
    public function delete(int \$id): bool;
}

PHP;
    }

    protected function buildService(string $name, string $model): string
    {
        $namespace = "Modules\\{$name}\\Services";
        $interfaceNamespace = "Modules\\{$name}\\Contracts\\{$name}ServiceInterface";
        $modelNamespace = "Modules\\{$name}\\Models\\{$model}";

        return <<<PHP
<?php

namespace {$namespace};

use App\Support\Services\BaseService;
use {$interfaceNamespace};
use {$modelNamespace};
use Illuminate\Database\Eloquent\Collection;

class {$name}Service extends BaseService implements {$name}ServiceInterface
{
    protected string \$modelClass = {$model}::class;

    protected array \$defaultResource = [
        //
    ];

    public function getAll(): Collection
    {
        return \$this->getAllRecords();
    }

    public function getById(int \$id): ?{$model}
    {
        return \$this->findOrFail(\$id);
    }

    public function store(array \$data): {$model}
    {
        return \$this->createRecord(\$data);
    }

    public function update(array \$data, int \$id): {$model}
    {
        return \$this->updateRecord(\$id, \$data);
    }

    public function delete(int \$id): bool
    {
        return \$this->deleteRecord(\$id);
    }
}

PHP;
    }
}
