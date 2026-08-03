<?php

namespace Modules\VoucherReference\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherReference\Models\VoucherReference;

interface VoucherReferenceServiceInterface extends BaseServiceInterface
{
    public function getByVoucherId(int $voucherId): Collection;

    public function getByReferenceVoucherId(int $voucherId): Collection;

    public function getByVoucherIdAndReferenceVoucherId(int $voucherId, int $refVoucherId): ?VoucherReference;

    public function getByVoucherIdOrReferenceVoucherId(int $voucherId, int $refVoucherId): ?Collection;
}
