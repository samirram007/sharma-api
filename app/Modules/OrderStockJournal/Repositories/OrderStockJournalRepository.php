<?php

namespace Modules\OrderStockJournal\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\OrderStockJournal\Contracts\OrderStockJournalRepositoryInterface;
use Modules\OrderStockJournal\Models\OrderStockJournal;

class OrderStockJournalRepository extends BaseRepository implements OrderStockJournalRepositoryInterface
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

    public function __construct(OrderStockJournal $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
