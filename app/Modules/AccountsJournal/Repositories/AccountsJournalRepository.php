<?php

namespace Modules\AccountsJournal\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AccountsJournal\Contracts\AccountsJournalRepositoryInterface;
use Modules\AccountsJournal\Models\AccountsJournal;

class AccountsJournalRepository extends BaseRepository implements AccountsJournalRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'entry_order',
        // 'debit',
        // 'credit',
        'remarks',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'voucher_id',
        // 'account_ledger_id',
    ];

    public function __construct(AccountsJournal $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
