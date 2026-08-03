<?php

namespace Modules\CostAllocationRule\Services;

use App\Support\Services\BaseService;
use Modules\CostAllocationRule\Contracts\CostAllocationRuleServiceInterface;
use Modules\CostAllocationRule\Facades\CostAllocationRuleRepositoryFacade;
use Modules\CostAllocationRule\Models\CostAllocationRule;

class CostAllocationRuleService extends BaseService implements CostAllocationRuleServiceInterface
{
    protected string $modelClass = CostAllocationRule::class;

    protected string $repositoryFacadeClass = CostAllocationRuleRepositoryFacade::class;

    public function __construct() {}
}
