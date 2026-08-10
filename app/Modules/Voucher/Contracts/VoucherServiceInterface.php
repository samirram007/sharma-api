<?php

namespace Modules\Voucher\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Voucher\Models\Voucher;

interface VoucherServiceInterface extends BaseServiceInterface
{
    /**
     * @param  int|null  $fiscalYearId  Override the user's assigned fiscal
     *                                  year scope (defaults to it).
     */
    public function getByModule(string $module, ?int $fiscalYearId = null): Collection;

    public function getByVoucherType(int $voucherTypeId): Collection;

    public function getPreviousYearClosingStock(): array;

    public function getOpeningStockVoucherType(): array;

    public function attachLedgerInfo(Voucher $voucher): Voucher;
}
