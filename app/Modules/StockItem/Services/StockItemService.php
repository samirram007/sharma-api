<?php

namespace Modules\StockItem\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItem\Contracts\StockItemServiceInterface;
use Modules\StockItem\Facades\StockItemRepositoryFacade;
use Modules\StockItem\Models\StockItem;

class StockItemService extends BaseService implements StockItemServiceInterface
{
    protected string $modelClass = StockItem::class;

    protected array $defaultResource = ['stock_unit', 'alternate_stock_unit'];

    protected string $repositoryFacadeClass = StockItemRepositoryFacade::class;

    public function __construct() {}

    public function getPurchasableStockItems(): Collection
    {
        return $this->queryWithResource()->get();
    }
}
