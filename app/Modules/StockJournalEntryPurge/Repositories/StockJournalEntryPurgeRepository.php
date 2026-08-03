<?php

namespace Modules\StockJournalEntryPurge\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeRepositoryInterface;
use Modules\StockJournalEntryPurge\Models\StockJournalEntryPurge;

class StockJournalEntryPurgeRepository extends BaseRepository implements StockJournalEntryPurgeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'purged_by',
        // 'purged_at',
        // 'reason',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'stock_journal_entry_id',
    ];

    public function __construct(StockJournalEntryPurge $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
