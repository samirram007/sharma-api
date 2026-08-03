<?php

namespace Modules\UniqueQuantityCode\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeRepositoryInterface;
use Modules\UniqueQuantityCode\Models\UniqueQuantityCode;

class UniqueQuantityCodeRepository extends BaseRepository implements UniqueQuantityCodeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
        // 'quantity_type',
    ];

    public function __construct(UniqueQuantityCode $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
