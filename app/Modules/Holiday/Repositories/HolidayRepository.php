<?php

namespace Modules\Holiday\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Holiday\Contracts\HolidayRepositoryInterface;
use Modules\Holiday\Models\Holiday;

class HolidayRepository extends BaseRepository implements HolidayRepositoryInterface
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

    public function __construct(Holiday $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
