<?php

namespace Modules\Designation\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Designation\Contracts\DesignationRepositoryInterface;
use Modules\Designation\Models\Designation;

class DesignationRepository extends BaseRepository implements DesignationRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(Designation $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
