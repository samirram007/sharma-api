<?php

namespace Modules\StockItemSerial\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockItemSerial\Contracts\StockItemSerialRepositoryInterface;
use Modules\StockItemSerial\Models\StockItemSerial;

class StockItemSerialRepository extends BaseRepository implements StockItemSerialRepositoryInterface
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

    public function __construct(StockItemSerial $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
