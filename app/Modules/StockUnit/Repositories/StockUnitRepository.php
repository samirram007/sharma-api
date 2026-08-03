<?php

namespace Modules\StockUnit\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockUnit\Contracts\StockUnitRepositoryInterface;
use Modules\StockUnit\Models\StockUnit;

class StockUnitRepository extends BaseRepository implements StockUnitRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
        // 'Pieces',
        // 'conversion_factor',
        // 'no_of_decimal_places',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
        // 'unit_type',
        // 'quantity_type',
        // 'unique_quantity_code_id',
        // 'primary_stock_unit_id',
        // 'secondary_stock_unit_id',
    ];

    public function __construct(StockUnit $model)
    {
        parent::__construct($model);
    }
}
