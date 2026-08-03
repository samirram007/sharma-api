<?php

namespace Modules\StockItem\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface StockItemServiceInterface extends BaseServiceInterface
{
    public function getPurchasableStockItems(): Collection;
}
