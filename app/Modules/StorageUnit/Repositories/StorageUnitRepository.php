<?php

namespace Modules\StorageUnit\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StorageUnit\Contracts\StorageUnitRepositoryInterface;
use Modules\StorageUnit\Models\StorageUnit;

class StorageUnitRepository extends BaseRepository implements StorageUnitRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
        // 'storage_unit_category',
        // 'our_stock_with_third_party',
        // 'third_party_stock_with_us',
        // 'capacity_value',
        // 'temperature_min',
        // 'temperature_max',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
        // 'storage_unit_type',
        // 'parent_id',
        // 'is_virtual',
        // 'is_mobile',
        // 'capacity_unit_id',
    ];

    public function __construct(StorageUnit $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
