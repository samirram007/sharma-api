<?php

namespace Modules\Payment\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Models\Payment;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherReference\Models\VoucherReference;

class PaymentService extends BaseService implements PaymentServiceInterface
{
    protected string $modelClass = Payment::class;

    protected array $defaultResource = [];

    public function getPaymentsByFreightId(int $freight_id): Collection
    {
        $paymentVoucherIds = VoucherReference::where('ref_voucher_id', $freight_id)
            ->where('type', 'freight_payment')
            ->pluck('voucher_id');

        if ($paymentVoucherIds->isEmpty()) {
            return collect();
        }

        return Voucher::whereIn('id', $paymentVoucherIds)->get();
    }
}
