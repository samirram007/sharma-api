<?php

namespace Modules\StockItemBatch\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockItemBatch\Contracts\StockItemBatchRepositoryInterface;
use Modules\StockItemBatch\Models\StockItemBatch;

class StockItemBatchRepository extends BaseRepository implements StockItemBatchRepositoryInterface
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

    public function __construct(StockItemBatch $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
