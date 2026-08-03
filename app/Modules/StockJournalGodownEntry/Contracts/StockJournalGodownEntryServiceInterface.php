<?php

namespace Modules\StockJournalGodownEntry\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface StockJournalGodownEntryServiceInterface extends BaseServiceInterface
{
    public function getByStockJournalEntryId(int $stockJournalEntryId): Collection;
}
