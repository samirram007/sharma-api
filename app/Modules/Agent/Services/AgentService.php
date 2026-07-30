<?php

namespace Modules\Agent\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Agent\Contracts\AgentServiceInterface;
use Modules\Agent\Models\Agent;

class AgentService extends BaseService implements AgentServiceInterface
{
    protected string $modelClass = Agent::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Agent
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Agent
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Agent
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
