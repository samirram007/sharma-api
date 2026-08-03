<?php

namespace Modules\TestItem\Services;

use App\Support\Services\BaseService;
use Modules\TestItem\Contracts\TestItemServiceInterface;
use Modules\TestItem\Facades\TestItemRepositoryFacade;
use Modules\TestItem\Models\TestItem;

class TestItemService extends BaseService implements TestItemServiceInterface
{
    protected string $modelClass = TestItem::class;

    protected string $repositoryFacadeClass = TestItemRepositoryFacade::class;

    public function __construct() {}
}
