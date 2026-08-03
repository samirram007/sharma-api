<?php

namespace Modules\AccountingPeriod\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AccountingPeriod\Contracts\AccountingPeriodRepositoryInterface;
use Modules\AccountingPeriod\Models\AccountingPeriod;

class AccountingPeriodRepository extends BaseRepository implements AccountingPeriodRepositoryInterface
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

    public function __construct(AccountingPeriod $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
