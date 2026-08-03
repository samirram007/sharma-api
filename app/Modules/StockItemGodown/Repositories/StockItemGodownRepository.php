<?php

namespace Modules\StockItemGodown\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockItemGodown\Contracts\StockItemGodownRepositoryInterface;
use Modules\StockItemGodown\Models\StockItemGodown;

class StockItemGodownRepository extends BaseRepository implements StockItemGodownRepositoryInterface
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

    public function __construct(StockItemGodown $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
