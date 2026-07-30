<?php

namespace Modules\AccountingPeriod\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AccountingPeriod\Contracts\AccountingPeriodServiceInterface;
use Modules\AccountingPeriod\Models\AccountingPeriod;

class AccountingPeriodService extends BaseService implements AccountingPeriodServiceInterface
{
    protected string $modelClass = AccountingPeriod::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?AccountingPeriod
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): AccountingPeriod
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): AccountingPeriod
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
