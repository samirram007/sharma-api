<?php

namespace Modules\VoucherEntry\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherEntry\Contracts\VoucherEntryRepositoryInterface;
use Modules\VoucherEntry\Models\VoucherEntry;

class VoucherEntryRepository extends BaseRepository implements VoucherEntryRepositoryInterface
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

    public function __construct(VoucherEntry $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
