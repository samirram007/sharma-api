<?php

namespace Modules\State\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\State\Contracts\StateRepositoryInterface;
use Modules\State\Models\State;

class StateRepository extends BaseRepository implements StateRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'gst_code',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'country_id',
    ];

    public function __construct(State $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
