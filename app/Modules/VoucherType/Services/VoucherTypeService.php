<?php

namespace Modules\VoucherType\Services;

use App\Support\Services\BaseService;
use Modules\VoucherType\Contracts\VoucherTypeServiceInterface;
use Modules\VoucherType\Facades\VoucherTypeRepositoryFacade;
use Modules\VoucherType\Models\VoucherType;

class VoucherTypeService extends BaseService implements VoucherTypeServiceInterface
{
    protected string $modelClass = VoucherType::class;

    protected array $defaultResource = [
        'voucher_category',
    ];

    protected string $repositoryFacadeClass = VoucherTypeRepositoryFacade::class;

    public function __construct() {}
}
