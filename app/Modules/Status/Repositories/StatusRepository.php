<?php

namespace Modules\Status\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Status\Contracts\StatusRepositoryInterface;
use Modules\Status\Models\Status;

class StatusRepository extends BaseRepository implements StatusRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        // 'color',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(Status $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
