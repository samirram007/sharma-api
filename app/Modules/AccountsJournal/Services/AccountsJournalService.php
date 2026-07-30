<?php

namespace Modules\AccountsJournal\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AccountsJournal\Contracts\AccountsJournalServiceInterface;
use Modules\AccountsJournal\Models\AccountsJournal;

class AccountsJournalService extends BaseService implements AccountsJournalServiceInterface
{
    protected string $modelClass = AccountsJournal::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?AccountsJournal
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): AccountsJournal
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): AccountsJournal
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
