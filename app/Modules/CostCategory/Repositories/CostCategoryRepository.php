<?php

namespace Modules\CostCategory\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\CostCategory\Contracts\CostCategoryRepositoryInterface;
use Modules\CostCategory\Models\CostCategory;

class CostCategoryRepository extends BaseRepository implements CostCategoryRepositoryInterface
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

    public function __construct(CostCategory $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
