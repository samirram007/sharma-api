<?php

namespace Modules\DeliveryPlace\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\DeliveryPlace\Contracts\DeliveryPlaceRepositoryInterface;
use Modules\DeliveryPlace\Models\DeliveryPlace;

class DeliveryPlaceRepository extends BaseRepository implements DeliveryPlaceRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'remarks',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'place_type',
        // 'is_active',
    ];

    public function __construct(DeliveryPlace $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
