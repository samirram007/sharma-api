<?php

namespace Modules\Vehicle\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Vehicle\Contracts\VehicleRepositoryInterface;
use Modules\Vehicle\Models\Vehicle;

class VehicleRepository extends BaseRepository implements VehicleRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'vehicle_no',
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

    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
