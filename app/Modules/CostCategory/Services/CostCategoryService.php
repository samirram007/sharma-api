<?php

namespace Modules\CostCategory\Services;

use App\Support\Services\BaseService;
use Modules\CostCategory\Contracts\CostCategoryServiceInterface;
use Modules\CostCategory\Facades\CostCategoryRepositoryFacade;
use Modules\CostCategory\Models\CostCategory;

class CostCategoryService extends BaseService implements CostCategoryServiceInterface
{
    protected string $modelClass = CostCategory::class;

    protected string $repositoryFacadeClass = CostCategoryRepositoryFacade::class;

    public function __construct() {}
}
