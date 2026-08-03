<?php

namespace Modules\HsnSacCode\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\HsnSacCode\Contracts\HsnSacCodeRepositoryInterface;
use Modules\HsnSacCode\Models\HsnSacCode;

class HsnSacCodeRepository extends BaseRepository implements HsnSacCodeRepositoryInterface
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

    public function __construct(HsnSacCode $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
