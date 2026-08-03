<?php

namespace Modules\Uqc\Services;

use App\Support\Services\BaseService;
use Modules\Uqc\Contracts\UqcServiceInterface;
use Modules\Uqc\Facades\UqcRepositoryFacade;
use Modules\Uqc\Models\Uqc;

class UqcService extends BaseService implements UqcServiceInterface
{
    protected string $modelClass = Uqc::class;

    protected string $repositoryFacadeClass = UqcRepositoryFacade::class;

    public function __construct() {}
}
