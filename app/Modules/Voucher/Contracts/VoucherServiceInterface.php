<?php

namespace Modules\Voucher\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Voucher\Models\Voucher;

interface VoucherServiceInterface extends BaseServiceInterface
{
    public function getByModule(string $module): Collection;

    public function getByVoucherType(int $voucherTypeId): Collection;

    public function attachLedgerInfo(Voucher $voucher): Voucher;
}
