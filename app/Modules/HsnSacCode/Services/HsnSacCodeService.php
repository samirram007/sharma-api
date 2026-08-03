<?php

namespace Modules\HsnSacCode\Services;

use App\Support\Services\BaseService;
use Modules\HsnSacCode\Contracts\HsnSacCodeServiceInterface;
use Modules\HsnSacCode\Facades\HsnSacCodeRepositoryFacade;
use Modules\HsnSacCode\Models\HsnSacCode;

class HsnSacCodeService extends BaseService implements HsnSacCodeServiceInterface
{
    protected string $modelClass = HsnSacCode::class;

    protected string $repositoryFacadeClass = HsnSacCodeRepositoryFacade::class;

    public function __construct() {}
}
