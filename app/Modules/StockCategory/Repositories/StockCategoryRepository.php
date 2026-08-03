<?php

namespace Modules\StockCategory\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockCategory\Contracts\StockCategoryRepositoryInterface;
use Modules\StockCategory\Models\StockCategory;

class StockCategoryRepository extends BaseRepository implements StockCategoryRepositoryInterface
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
        // 'parent_id',
    ];

    public function __construct(StockCategory $model)
    {
        parent::__construct($model);
    }
}
