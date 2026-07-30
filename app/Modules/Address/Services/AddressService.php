<?php

namespace Modules\Address\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Address\Contracts\AddressServiceInterface;
use Modules\Address\Models\Address;

class AddressService extends BaseService implements AddressServiceInterface
{
    protected string $modelClass = Address::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Address
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Address
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Address
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
