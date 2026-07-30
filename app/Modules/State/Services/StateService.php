<?php

namespace Modules\State\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\State\Contracts\StateServiceInterface;
use Modules\State\Models\State;

class StateService extends BaseService implements StateServiceInterface
{
    protected string $modelClass = State::class;

    protected array $defaultResource = [
        'country',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?State
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): State
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): State
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
