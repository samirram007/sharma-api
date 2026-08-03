<?php

namespace Modules\VoucherDispatchDetail\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailRepositoryInterface;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;

class VoucherDispatchDetailRepository extends BaseRepository implements VoucherDispatchDetailRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        // 'order_number',
        // 'payment_terms',
        // 'other_references',
        // 'terms_of_delivery',
        // 'receipt_doc_no',
        // 'dispatched_through',
        // 'source',
        // 'destination',
        // 'destination_secondary',
        // 'billing_preference',
        // 'carrier_name',
        // 'bill_of_lading_no',
        // 'bill_of_lading_date',
        // 'motor_vehicle_no',
        // 'distance',
        // 'rate',
        // 'quantity',
        // 'weight',
        // 'volume',
        // 'freight_basis',
        // 'loading_charges',
        // 'unloading_charges',
        // 'packing_charges',
        // 'insurance_charges',
        // 'other_charges',
        // 'freight_charges',
        // 'total_fare',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'voucher_id',
        // 'distance_unit_id',
        // 'rate_unit_id',
        // 'quantity_unit_id',
        // 'weight_unit_id',
        // 'volume_unit_id',
    ];

    public function __construct(VoucherDispatchDetail $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
