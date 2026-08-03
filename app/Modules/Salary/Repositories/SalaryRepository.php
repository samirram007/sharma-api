<?php

namespace Modules\Salary\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Salary\Contracts\SalaryRepositoryInterface;
use Modules\Salary\Models\Salary;

class SalaryRepository extends BaseRepository implements SalaryRepositoryInterface
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

    public function __construct(Salary $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
