<?php

namespace Modules\VoucherPaymentMode\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeRepositoryInterface;
use Modules\VoucherPaymentMode\Models\VoucherPaymentMode;

class VoucherPaymentModeRepository extends BaseRepository implements VoucherPaymentModeRepositoryInterface
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

    public function __construct(VoucherPaymentMode $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
