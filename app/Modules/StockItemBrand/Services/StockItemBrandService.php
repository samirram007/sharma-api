<?php

namespace Modules\StockItemBrand\Services;

use App\Support\Services\BaseService;
use Modules\StockItemBrand\Contracts\StockItemBrandServiceInterface;
use Modules\StockItemBrand\Facades\StockItemBrandRepositoryFacade;
use Modules\StockItemBrand\Models\StockItemBrand;

class StockItemBrandService extends BaseService implements StockItemBrandServiceInterface
{
    protected string $modelClass = StockItemBrand::class;

    protected string $repositoryFacadeClass = StockItemBrandRepositoryFacade::class;

    public function __construct() {}
}
