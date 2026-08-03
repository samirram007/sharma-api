<?php

namespace Modules\StockJournalBatchEntry\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryRepositoryInterface;
use Modules\StockJournalBatchEntry\Models\StockJournalBatchEntry;

class StockJournalBatchEntryRepository extends BaseRepository implements StockJournalBatchEntryRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'batch_no',
        // 'mfg_date',
        // 'expiry_date',
        // 'serial_no',
        // 'quantity',
        // 'rate',
        // 'amount',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'stock_journal_godown_entry_id',
        // 'movement_type',
    ];

    public function __construct(StockJournalBatchEntry $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
