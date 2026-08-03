<?php

namespace Modules\Branch\Services;

use App\Support\Services\BaseService;
use Modules\Branch\Contracts\BranchServiceInterface;
use Modules\Branch\Facades\BranchRepositoryFacade;
use Modules\Branch\Models\Branch;

class BranchService extends BaseService implements BranchServiceInterface
{
    protected string $modelClass = Branch::class;

    protected string $repositoryFacadeClass = BranchRepositoryFacade::class;

    public function __construct() {}
}
