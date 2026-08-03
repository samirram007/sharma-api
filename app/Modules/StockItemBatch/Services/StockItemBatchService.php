<?php

namespace Modules\StockItemBatch\Services;

use App\Support\Services\BaseService;
use Modules\StockItemBatch\Contracts\StockItemBatchServiceInterface;
use Modules\StockItemBatch\Facades\StockItemBatchRepositoryFacade;
use Modules\StockItemBatch\Models\StockItemBatch;

class StockItemBatchService extends BaseService implements StockItemBatchServiceInterface
{
    protected string $modelClass = StockItemBatch::class;

    protected string $repositoryFacadeClass = StockItemBatchRepositoryFacade::class;

    public function __construct() {}
}
