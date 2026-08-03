<?php

namespace Modules\VoucherCategory\Services;

use App\Support\Services\BaseService;
use Modules\VoucherCategory\Contracts\VoucherCategoryServiceInterface;
use Modules\VoucherCategory\Facades\VoucherCategoryRepositoryFacade;
use Modules\VoucherCategory\Models\VoucherCategory;

class VoucherCategoryService extends BaseService implements VoucherCategoryServiceInterface
{
    protected string $modelClass = VoucherCategory::class;

    protected array $defaultResource = [
        'voucher_types',
    ];

    protected string $repositoryFacadeClass = VoucherCategoryRepositoryFacade::class;

    public function __construct() {}
}
