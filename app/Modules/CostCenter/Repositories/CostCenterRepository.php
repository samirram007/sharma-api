<?php

namespace Modules\CostCenter\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\CostCenter\Contracts\CostCenterRepositoryInterface;
use Modules\CostCenter\Models\CostCenter;

class CostCenterRepository extends BaseRepository implements CostCenterRepositoryInterface
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

    public function __construct(CostCenter $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
