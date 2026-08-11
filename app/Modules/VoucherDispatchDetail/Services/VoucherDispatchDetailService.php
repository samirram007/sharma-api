<?php

namespace Modules\VoucherDispatchDetail\Services;

use App\Support\Services\BaseService;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Facades\VoucherDispatchDetailRepositoryFacade;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;

class VoucherDispatchDetailService extends BaseService implements VoucherDispatchDetailServiceInterface
{
    protected string $modelClass = VoucherDispatchDetail::class;

    protected string $repositoryFacadeClass = VoucherDispatchDetailRepositoryFacade::class;

    public function __construct() {}

    /**
     * Save dispatch details as an upsert keyed on the unique voucher_id.
     *
     * Dispatch details are 1:1 per voucher (voucher_id is unique), and the
     * frontend may send a stale record id that belongs to a different voucher
     * (e.g. cached row data). Keying the write on voucher_id — ignoring the
     * client-supplied id — keeps the save idempotent and prevents the
     * "Duplicate entry ... for key voucher_dispatch_details_voucher_id_unique"
     * error when an update would otherwise reassign another voucher's row.
     */
    public function store(array $data): VoucherDispatchDetail
    {
        return $this->upsertByVoucher($data);
    }

    public function update(array $data, int $id): VoucherDispatchDetail
    {
        // $id (URL / stale form id) is intentionally ignored: the unique
        // voucher_id in the payload is the source of truth for the target row.
        return $this->upsertByVoucher($data);
    }

    /**
     * Find the dispatch detail for a voucher and update it, or create it when
     * the voucher has none yet.
     */
    protected function upsertByVoucher(array $data): VoucherDispatchDetail
    {
        $voucherId = (int) ($data['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            throw new \InvalidArgumentException('voucher_id is required to save dispatch details.');
        }

        unset($data['id']);

        $existing = $this->modelClass::where('voucher_id', $voucherId)->first();

        if ($existing) {
            $existing->fill($data)->save();
            $record = $existing->fresh();
        } else {
            $record = $this->modelClass::create($data);
        }

        // Direct model writes bypass the repository, so keep its cache in sync.
        $repo = $this->getRepository();
        if ($repo) {
            $repo->clearCache();
        }

        return $record;
    }
}
