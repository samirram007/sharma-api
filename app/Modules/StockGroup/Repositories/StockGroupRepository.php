<?php

namespace Modules\StockGroup\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockGroup\Contracts\StockGroupRepositoryInterface;
use Modules\StockGroup\Models\StockGroup;

class StockGroupRepository extends BaseRepository implements StockGroupRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        // 'should_quantities_of_items_be_added',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
        // 'parent_id',
    ];

    public function __construct(StockGroup $model)
    {
        parent::__construct($model);
    }
}
