<?php

namespace Modules\Grade\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Grade\Contracts\GradeRepositoryInterface;
use Modules\Grade\Models\Grade;

class GradeRepository extends BaseRepository implements GradeRepositoryInterface
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

    public function __construct(Grade $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
