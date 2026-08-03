<?php

namespace Modules\Purchase\Services;

use App\Support\Services\BaseService;
use Modules\Purchase\Contracts\PurchaseServiceInterface;
use Modules\Purchase\Models\Purchase;

class PurchaseService extends BaseService implements PurchaseServiceInterface
{
    protected string $modelClass = Purchase::class;

    protected array $defaultResource = [];
}
