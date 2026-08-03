<?php

namespace Modules\VoucherNo\Services;

use App\Support\Services\BaseService;
use Modules\VoucherNo\Contracts\VoucherNoServiceInterface;
use Modules\VoucherNo\Models\VoucherNo;

class VoucherNoService extends BaseService implements VoucherNoServiceInterface
{
    protected string $modelClass = VoucherNo::class;

    protected array $defaultResource = [];
}
