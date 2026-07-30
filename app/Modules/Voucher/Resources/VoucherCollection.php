<?php

namespace Modules\Voucher\Resources;

use App\Http\Resources\SuccessCollection;

class VoucherCollection extends SuccessCollection
{
    public $collects = VoucherResource::class;

    public function __construct($resource, ?string $message = null)
    {
        parent::__construct(
            $resource,
            $message ?? 'Voucher records fetched successfully'
        );
    }
}
