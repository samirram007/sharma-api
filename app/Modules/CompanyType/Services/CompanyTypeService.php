<?php

namespace Modules\CompanyType\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyType\Contracts\CompanyTypeServiceInterface;
use Modules\CompanyType\Models\CompanyType;

class CompanyTypeService extends BaseService implements CompanyTypeServiceInterface
{
    protected string $modelClass = CompanyType::class;

    protected array $defaultResource = [
        'companies',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?CompanyType
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): CompanyType
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): CompanyType
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
