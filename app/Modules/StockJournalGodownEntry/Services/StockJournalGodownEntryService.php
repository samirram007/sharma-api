<?php

namespace Modules\StockJournalGodownEntry\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalGodownEntry\Contracts\StockJournalGodownEntryServiceInterface;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;

class StockJournalGodownEntryService extends BaseService implements StockJournalGodownEntryServiceInterface
{
    protected string $modelClass = StockJournalGodownEntry::class;

    protected array $defaultResource = [];

    public function getByStockJournalEntryId(int $stockJournalEntryId): Collection
    {
        return StockJournalGodownEntry::with($this->defaultResource)
            ->where('stock_journal_entry_id', $stockJournalEntryId)
            ->get();
    }
}
