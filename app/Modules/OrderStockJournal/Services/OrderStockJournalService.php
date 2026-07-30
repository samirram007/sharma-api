<?php

namespace Modules\OrderStockJournal\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\OrderStockJournal\Contracts\OrderStockJournalServiceInterface;
use Modules\OrderStockJournal\Models\OrderStockJournal;

class OrderStockJournalService extends BaseService implements OrderStockJournalServiceInterface
{
    protected string $modelClass = OrderStockJournal::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?OrderStockJournal
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): OrderStockJournal
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): OrderStockJournal
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
