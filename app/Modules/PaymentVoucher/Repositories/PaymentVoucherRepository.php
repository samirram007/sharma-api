<?php

namespace Modules\PaymentVoucher\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\PaymentVoucher\Contracts\PaymentVoucherRepositoryInterface;
use Modules\PaymentVoucher\Models\PaymentVoucher;

class PaymentVoucherRepository extends BaseRepository implements PaymentVoucherRepositoryInterface
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

    public function __construct(PaymentVoucher $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
