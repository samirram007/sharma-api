<?php

namespace Modules\CostAllocationRule\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\CostAllocationRule\Contracts\CostAllocationRuleRepositoryInterface;
use Modules\CostAllocationRule\Models\CostAllocationRule;

class CostAllocationRuleRepository extends BaseRepository implements CostAllocationRuleRepositoryInterface
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

    public function __construct(CostAllocationRule $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
