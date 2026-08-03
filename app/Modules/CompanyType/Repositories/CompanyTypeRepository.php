<?php

namespace Modules\CompanyType\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\CompanyType\Contracts\CompanyTypeRepositoryInterface;
use Modules\CompanyType\Models\CompanyType;

class CompanyTypeRepository extends BaseRepository implements CompanyTypeRepositoryInterface
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

    public function __construct(CompanyType $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
