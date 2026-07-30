<?php

namespace Modules\AppModule\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AppModule\Contracts\AppModuleServiceInterface;
use Modules\AppModule\Models\AppModule;

class AppModuleService extends BaseService implements AppModuleServiceInterface
{
    protected string $modelClass = AppModule::class;

    protected array $defaultResource = [
        'app_module_features',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?AppModule
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): AppModule
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): AppModule
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
