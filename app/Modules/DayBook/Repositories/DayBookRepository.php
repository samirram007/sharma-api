<?php

namespace Modules\DayBook\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\DayBook\Contracts\DayBookRepositoryInterface;
use Modules\DayBook\Models\DayBook;

class DayBookRepository extends BaseRepository implements DayBookRepositoryInterface
{
    protected array $searchableFields = [
        // 'name',
        // 'code',
        // 'description',
    ];

    protected array $filterableFields = [
        // 'status',
    ];

    public function __construct(DayBook $model)
    {
        parent::__construct($model);
    }
}
