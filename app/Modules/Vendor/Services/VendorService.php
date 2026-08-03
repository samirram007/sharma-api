<?php

namespace Modules\Vendor\Services;

use App\Support\Services\BaseService;
use Modules\Vendor\Contracts\VendorServiceInterface;
use Modules\Vendor\Models\Vendor;

class VendorService extends BaseService implements VendorServiceInterface
{
    protected string $modelClass = Vendor::class;

    protected array $defaultResource = [];
}
