<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeRepositoryInterface;
use Modules\StockJournalStorageUnitEntryPurge\Models\StockJournalStorageUnitEntryPurge;

class StockJournalStorageUnitEntryPurgeRepository extends BaseRepository implements StockJournalStorageUnitEntryPurgeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(StockJournalStorageUnitEntryPurge $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
