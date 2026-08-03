<?php

namespace Modules\AccountLedger\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AccountLedger\Contracts\AccountLedgerRepositoryInterface;
use Modules\AccountLedger\Models\AccountLedger;

class AccountLedgerRepository extends BaseRepository implements AccountLedgerRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'account_group_id',
        'status',
    ];

    public function __construct(AccountLedger $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
