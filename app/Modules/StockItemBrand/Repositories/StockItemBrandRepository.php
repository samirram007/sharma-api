<?php

namespace Modules\StockItemBrand\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockItemBrand\Contracts\StockItemBrandRepositoryInterface;
use Modules\StockItemBrand\Models\StockItemBrand;

class StockItemBrandRepository extends BaseRepository implements StockItemBrandRepositoryInterface
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

    public function __construct(StockItemBrand $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
