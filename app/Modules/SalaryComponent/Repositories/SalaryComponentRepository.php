<?php

namespace Modules\SalaryComponent\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\SalaryComponent\Contracts\SalaryComponentRepositoryInterface;
use Modules\SalaryComponent\Models\SalaryComponent;

class SalaryComponentRepository extends BaseRepository implements SalaryComponentRepositoryInterface
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

    public function __construct(SalaryComponent $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
