<?php

namespace Modules\Voucher\Contracts;

use App\Support\Contracts\BaseRepositoryInterface;

interface VoucherRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a voucher by ID with shared lock (for transaction safety).
     */
    public function findWithLock(int $id): mixed;
}
