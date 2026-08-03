<?php

namespace Modules\PaymentVoucher\Services;

use App\Support\Services\BaseService;
use Modules\PaymentVoucher\Contracts\PaymentVoucherServiceInterface;
use Modules\PaymentVoucher\Facades\PaymentVoucherRepositoryFacade;
use Modules\PaymentVoucher\Models\PaymentVoucher;

class PaymentVoucherService extends BaseService implements PaymentVoucherServiceInterface
{
    protected string $modelClass = PaymentVoucher::class;

    protected string $repositoryFacadeClass = PaymentVoucherRepositoryFacade::class;

    public function __construct() {}
}
