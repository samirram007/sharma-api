<?php

namespace Modules\Uqc\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Uqc\Contracts\UqcRepositoryInterface;
use Modules\Uqc\Models\Uqc;

class UqcRepository extends BaseRepository implements UqcRepositoryInterface
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

    public function __construct(Uqc $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
