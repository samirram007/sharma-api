<?php

namespace Modules\AccountLedger\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface AccountLedgerServiceInterface extends BaseServiceInterface
{
    public function getLedgerBalance(int $id): ?array;

    public function getPurchaseLedgers(): Collection;

    public function getSaleLedgers(): Collection;

    public function getSupplierLedgers(): Collection;

    public function getDistributorLedgers(): Collection;

    public function getStockInHandLedgers(): Collection;
}
