<?php

namespace Modules\Status\Services;

use App\Support\Services\BaseService;
use Modules\Status\Contracts\StatusServiceInterface;
use Modules\Status\Facades\StatusRepositoryFacade;
use Modules\Status\Models\Status;

class StatusService extends BaseService implements StatusServiceInterface
{
    protected string $modelClass = Status::class;

    protected string $repositoryFacadeClass = StatusRepositoryFacade::class;

    public function __construct() {}
}
