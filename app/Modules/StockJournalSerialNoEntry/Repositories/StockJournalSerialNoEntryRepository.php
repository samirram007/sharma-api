<?php

namespace Modules\StockJournalSerialNoEntry\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockJournalSerialNoEntry\Contracts\StockJournalSerialNoEntryRepositoryInterface;
use Modules\StockJournalSerialNoEntry\Models\StockJournalSerialNoEntry;

class StockJournalSerialNoEntryRepository extends BaseRepository implements StockJournalSerialNoEntryRepositoryInterface
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

    public function __construct(StockJournalSerialNoEntry $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
