<?php

namespace Modules\ReceiptVoucher\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Voucher\Models\Voucher;

interface ReceiptVoucherServiceInterface extends BaseServiceInterface
{
    public function getFreightReceiptByFreightId(int $freight_id): Collection;

    public function storeFreightReceiptVoucher(array $data): Voucher;
}
