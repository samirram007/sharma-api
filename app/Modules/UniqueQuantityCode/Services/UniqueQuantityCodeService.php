<?php

namespace Modules\UniqueQuantityCode\Services;

use App\Support\Services\BaseService;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeServiceInterface;
use Modules\UniqueQuantityCode\Facades\UniqueQuantityCodeRepositoryFacade;
use Modules\UniqueQuantityCode\Models\UniqueQuantityCode;

class UniqueQuantityCodeService extends BaseService implements UniqueQuantityCodeServiceInterface
{
    protected string $modelClass = UniqueQuantityCode::class;

    protected string $repositoryFacadeClass = UniqueQuantityCodeRepositoryFacade::class;

    public function __construct() {}
}
