<?php

namespace Modules\DistributorBook\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\DistributorBook\Contracts\DistributorBookRepositoryInterface;
use Modules\DistributorBook\Models\DistributorBook;

class DistributorBookRepository extends BaseRepository implements DistributorBookRepositoryInterface
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

    public function __construct(DistributorBook $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
