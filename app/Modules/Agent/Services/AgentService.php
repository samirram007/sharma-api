<?php

namespace Modules\Agent\Services;

use App\Support\Services\BaseService;
use Modules\Agent\Contracts\AgentServiceInterface;
use Modules\Agent\Facades\AgentRepositoryFacade;
use Modules\Agent\Models\Agent;

class AgentService extends BaseService implements AgentServiceInterface
{
    protected string $modelClass = Agent::class;

    protected string $repositoryFacadeClass = AgentRepositoryFacade::class;

    public function __construct() {}
}
