<?php

namespace Modules\StockJournalGodownEntryPurge\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeRepositoryInterface;
use Modules\StockJournalGodownEntryPurge\Models\StockJournalGodownEntryPurge;

class StockJournalGodownEntryPurgeRepository extends BaseRepository implements StockJournalGodownEntryPurgeRepositoryInterface
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
        // 'stock_journal_godown_entry_id',
    ];

    public function __construct(StockJournalGodownEntryPurge $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
