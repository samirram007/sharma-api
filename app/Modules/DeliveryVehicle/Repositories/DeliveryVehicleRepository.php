<?php

namespace Modules\DeliveryVehicle\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\DeliveryVehicle\Contracts\DeliveryVehicleRepositoryInterface;
use Modules\DeliveryVehicle\Models\DeliveryVehicle;

class DeliveryVehicleRepository extends BaseRepository implements DeliveryVehicleRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'vehicle_number',
        // 'capacity',
        // 'driver_name',
        // 'driver_contact',
        'description',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'transporter_id',
        // 'vehicle_type',
        'status',
    ];

    public function __construct(DeliveryVehicle $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
