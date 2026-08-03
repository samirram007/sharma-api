<?php

namespace Modules\OrderJournal\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\OrderJournal\Contracts\OrderJournalRepositoryInterface;
use Modules\OrderJournal\Models\OrderJournal;

class OrderJournalRepository extends BaseRepository implements OrderJournalRepositoryInterface
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

    public function __construct(OrderJournal $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
