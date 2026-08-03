<?php

namespace Modules\SalaryStructure\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\SalaryStructure\Contracts\SalaryStructureRepositoryInterface;
use Modules\SalaryStructure\Models\SalaryStructure;

class SalaryStructureRepository extends BaseRepository implements SalaryStructureRepositoryInterface
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

    public function __construct(SalaryStructure $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
