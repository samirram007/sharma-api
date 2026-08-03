<?php

namespace Modules\StockItemPrice\Services;

use App\Support\Services\BaseService;
use Modules\StockItemPrice\Contracts\StockItemPriceServiceInterface;
use Modules\StockItemPrice\Facades\StockItemPriceRepositoryFacade;
use Modules\StockItemPrice\Models\StockItemPrice;

class StockItemPriceService extends BaseService implements StockItemPriceServiceInterface
{
    protected string $modelClass = StockItemPrice::class;

    protected string $repositoryFacadeClass = StockItemPriceRepositoryFacade::class;

    public function __construct() {}
}
