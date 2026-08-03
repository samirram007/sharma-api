<?php

namespace Modules\Branch\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Branch\Contracts\BranchRepositoryInterface;
use Modules\Branch\Models\Branch;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
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

    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }
}
