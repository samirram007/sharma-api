<?php

namespace Modules\StockJournalStorageUnitEntry\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockJournalStorageUnitEntry\Contracts\StockJournalStorageUnitEntryRepositoryInterface;
use Modules\StockJournalStorageUnitEntry\Models\StockJournalStorageUnitEntry;

class StockJournalStorageUnitEntryRepository extends BaseRepository implements StockJournalStorageUnitEntryRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'entry_order',
        // 'batch_no',
        // 'mfg_date',
        // 'expiry_date',
        // 'serial_no',
        // 'actual_quantity',
        // 'billing_quantity',
        // 'rate',
        // 'discount_percentage',
        // 'discount',
        // 'amount',
        'remarks',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'stock_journal_entry_id',
        // 'storage_unit_id',
        // 'movement_type',
    ];

    public function __construct(StockJournalStorageUnitEntry $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
