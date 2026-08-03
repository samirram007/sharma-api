<?php

namespace Modules\StockUnit\Services;

use App\Support\Services\BaseService;
use Modules\StockUnit\Contracts\StockUnitServiceInterface;
use Modules\StockUnit\Facades\StockUnitRepositoryFacade;
use Modules\StockUnit\Models\StockUnit;

class StockUnitService extends BaseService implements StockUnitServiceInterface
{
    protected string $modelClass = StockUnit::class;

    protected array $defaultResource = [
        'primary_stock_unit',
        'secondary_stock_unit',
        'unique_quantity_code',
    ];

    protected string $repositoryFacadeClass = StockUnitRepositoryFacade::class;

    public function __construct() {}
}
