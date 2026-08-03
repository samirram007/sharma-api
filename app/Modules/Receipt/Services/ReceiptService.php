<?php

namespace Modules\Receipt\Services;

use App\Support\Services\BaseService;
use Modules\Receipt\Contracts\ReceiptServiceInterface;
use Modules\Receipt\Models\Receipt;

class ReceiptService extends BaseService implements ReceiptServiceInterface
{
    protected string $modelClass = Receipt::class;

    protected array $defaultResource = [];
}
