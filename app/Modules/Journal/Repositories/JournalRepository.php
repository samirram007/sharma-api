<?php

namespace Modules\Journal\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Journal\Contracts\JournalRepositoryInterface;
use Modules\Journal\Models\Journal;

class JournalRepository extends BaseRepository implements JournalRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
    ];

    public function __construct(Journal $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
