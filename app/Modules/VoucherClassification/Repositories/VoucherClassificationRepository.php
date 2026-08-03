<?php

namespace Modules\VoucherClassification\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherClassification\Contracts\VoucherClassificationRepositoryInterface;
use Modules\VoucherClassification\Models\VoucherClassification;

class VoucherClassificationRepository extends BaseRepository implements VoucherClassificationRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        // 'requires_approval',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'company_id',
        // 'branch_id',
        // 'voucher_type_id',
        'status',
        // 'is_default',
        // 'is_system_defined',
    ];

    public function __construct(VoucherClassification $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
