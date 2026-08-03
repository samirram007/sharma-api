<?php

namespace Modules\VoucherReference\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherReference\Contracts\VoucherReferenceServiceInterface;
use Modules\VoucherReference\Models\VoucherReference;

class VoucherReferenceService extends BaseService implements VoucherReferenceServiceInterface
{
    protected string $modelClass = VoucherReference::class;

    protected array $defaultResource = ['voucher', 'reference_voucher'];

    public function getByVoucherId(int $voucherId): Collection
    {
        return VoucherReference::with($this->defaultResource)
            ->where('voucher_id', $voucherId)
            ->get();
    }

    public function getByReferenceVoucherId(int $voucherId): Collection
    {
        return VoucherReference::with($this->defaultResource)
            ->where('ref_voucher_id', $voucherId)
            ->get();
    }

    public function getByVoucherIdAndReferenceVoucherId(int $voucherId, int $refVoucherId): ?VoucherReference
    {
        return VoucherReference::with($this->defaultResource)
            ->where('voucher_id', $voucherId)
            ->where('ref_voucher_id', $refVoucherId)
            ->first();
    }

    public function getByVoucherIdOrReferenceVoucherId(int $voucherId, int $refVoucherId): ?Collection
    {
        return VoucherReference::with($this->defaultResource)
            ->where('voucher_id', $voucherId)
            ->OrWhere('ref_voucher_id', $refVoucherId)
            ->get();
    }
}
