<?php

namespace Modules\Journal\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Journal\Contracts\JournalServiceInterface;
use Modules\Journal\Models\Journal;

class JournalService extends BaseService implements JournalServiceInterface
{
    protected string $modelClass = Journal::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Journal
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Journal
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Journal
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
