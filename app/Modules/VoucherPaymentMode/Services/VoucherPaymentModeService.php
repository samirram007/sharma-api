<?php

namespace Modules\VoucherPaymentMode\Services;

use App\Support\Services\BaseService;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeServiceInterface;
use Modules\VoucherPaymentMode\Facades\VoucherPaymentModeRepositoryFacade;
use Modules\VoucherPaymentMode\Models\VoucherPaymentMode;

class VoucherPaymentModeService extends BaseService implements VoucherPaymentModeServiceInterface
{
    protected string $modelClass = VoucherPaymentMode::class;

    protected string $repositoryFacadeClass = VoucherPaymentModeRepositoryFacade::class;

    public function __construct() {}
}
