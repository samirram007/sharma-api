<?php

namespace Modules\Shift\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Shift\Contracts\ShiftRepositoryInterface;
use Modules\Shift\Models\Shift;

class ShiftRepository extends BaseRepository implements ShiftRepositoryInterface
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

    public function __construct(Shift $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
