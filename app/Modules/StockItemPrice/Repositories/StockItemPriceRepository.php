<?php

namespace Modules\StockItemPrice\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockItemPrice\Contracts\StockItemPriceRepositoryInterface;
use Modules\StockItemPrice\Models\StockItemPrice;

class StockItemPriceRepository extends BaseRepository implements StockItemPriceRepositoryInterface
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

    public function __construct(StockItemPrice $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
