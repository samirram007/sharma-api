<?php

namespace Modules\VoucherEntryPurge\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeRepositoryInterface;
use Modules\VoucherEntryPurge\Models\VoucherEntryPurge;

class VoucherEntryPurgeRepository extends BaseRepository implements VoucherEntryPurgeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'purged_by',
        // 'purged_at',
        // 'reason',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'voucher_entry_id',
    ];

    public function __construct(VoucherEntryPurge $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
