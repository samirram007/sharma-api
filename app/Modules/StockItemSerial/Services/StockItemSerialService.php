<?php

namespace Modules\StockItemSerial\Services;

use App\Support\Services\BaseService;
use Modules\StockItemSerial\Contracts\StockItemSerialServiceInterface;
use Modules\StockItemSerial\Facades\StockItemSerialRepositoryFacade;
use Modules\StockItemSerial\Models\StockItemSerial;

class StockItemSerialService extends BaseService implements StockItemSerialServiceInterface
{
    protected string $modelClass = StockItemSerial::class;

    protected string $repositoryFacadeClass = StockItemSerialRepositoryFacade::class;

    public function __construct() {}
}
