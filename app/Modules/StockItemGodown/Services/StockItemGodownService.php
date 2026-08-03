<?php

namespace Modules\StockItemGodown\Services;

use App\Support\Services\BaseService;
use Modules\StockItemGodown\Contracts\StockItemGodownServiceInterface;
use Modules\StockItemGodown\Facades\StockItemGodownRepositoryFacade;
use Modules\StockItemGodown\Models\StockItemGodown;

class StockItemGodownService extends BaseService implements StockItemGodownServiceInterface
{
    protected string $modelClass = StockItemGodown::class;

    protected string $repositoryFacadeClass = StockItemGodownRepositoryFacade::class;

    public function __construct() {}
}
