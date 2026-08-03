<?php

namespace Modules\VoucherClassification\Services;

use App\Support\Services\BaseService;
use Modules\VoucherClassification\Contracts\VoucherClassificationServiceInterface;
use Modules\VoucherClassification\Facades\VoucherClassificationRepositoryFacade;
use Modules\VoucherClassification\Models\VoucherClassification;

class VoucherClassificationService extends BaseService implements VoucherClassificationServiceInterface
{
    protected string $modelClass = VoucherClassification::class;

    protected array $defaultResource = [
        'voucher_type',
    ];

    protected string $repositoryFacadeClass = VoucherClassificationRepositoryFacade::class;

    public function __construct() {}
}
