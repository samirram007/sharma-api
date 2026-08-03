<?php

namespace Modules\AppMaintenance\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AppMaintenance\Contracts\AppMaintenanceRepositoryInterface;
use Modules\AppMaintenance\Models\AppMaintenance;

class AppMaintenanceRepository extends BaseRepository implements AppMaintenanceRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
    ];

    public function __construct(AppMaintenance $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
