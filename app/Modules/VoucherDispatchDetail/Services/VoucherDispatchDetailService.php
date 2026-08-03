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
}
