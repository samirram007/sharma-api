<?php

namespace Modules\StockGroup\Services;

use App\Support\Services\BaseService;
use Modules\StockGroup\Contracts\StockGroupServiceInterface;
use Modules\StockGroup\Facades\StockGroupRepositoryFacade;
use Modules\StockGroup\Models\StockGroup;

class StockGroupService extends BaseService implements StockGroupServiceInterface
{
    protected string $modelClass = StockGroup::class;

    protected array $defaultResource = [
        'parent',
    ];

    protected string $repositoryFacadeClass = StockGroupRepositoryFacade::class;

    public function __construct() {}
}
