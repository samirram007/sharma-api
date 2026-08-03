<?php

namespace Modules\StockCategory\Services;

use App\Support\Services\BaseService;
use Modules\StockCategory\Contracts\StockCategoryServiceInterface;
use Modules\StockCategory\Facades\StockCategoryRepositoryFacade;
use Modules\StockCategory\Models\StockCategory;

class StockCategoryService extends BaseService implements StockCategoryServiceInterface
{
    protected string $modelClass = StockCategory::class;

    protected array $defaultResource = [
        'parent',
    ];

    protected string $repositoryFacadeClass = StockCategoryRepositoryFacade::class;

    public function __construct() {}
}
