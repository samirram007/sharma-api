<?php

namespace Modules\Currency\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Currency\Contracts\CurrencyServiceInterface;
use Modules\Currency\Models\Currency;

class CurrencyService extends BaseService implements CurrencyServiceInterface
{
    protected string $modelClass = Currency::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): Currency
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Currency
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Currency
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
