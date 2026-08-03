<?php

namespace Modules\StockJournalEntry\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface StockJournalEntryServiceInterface extends BaseServiceInterface
{
    public function getByStockJournalId(int $stockJournalId): Collection;
}
