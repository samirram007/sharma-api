<?php

namespace Modules\DeliveryRoute\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\DeliveryRoute\Contracts\DeliveryRouteRepositoryInterface;
use Modules\DeliveryRoute\Models\DeliveryRoute;

class DeliveryRouteRepository extends BaseRepository implements DeliveryRouteRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'vehicle_no',
        // 'distance_km',
        // 'rate',
        // 'estimated_time_in_minutes',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'transporter_id',
        // 'source_place_id',
        // 'destination_place_id',
        // 'rate_unit_id',
    ];

    public function __construct(DeliveryRoute $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
