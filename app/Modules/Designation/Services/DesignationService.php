<?php

namespace Modules\Designation\Services;

use App\Support\Services\BaseService;
use Modules\Designation\Contracts\DesignationServiceInterface;
use Modules\Designation\Facades\DesignationRepositoryFacade;
use Modules\Designation\Models\Designation;

class DesignationService extends BaseService implements DesignationServiceInterface
{
    protected string $modelClass = Designation::class;

    protected string $repositoryFacadeClass = DesignationRepositoryFacade::class;

    public function __construct() {}
}
