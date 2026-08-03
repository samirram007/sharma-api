<?php

namespace Modules\GstRegistrationType\Services;

use App\Support\Services\BaseService;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeServiceInterface;
use Modules\GstRegistrationType\Facades\GstRegistrationTypeRepositoryFacade;
use Modules\GstRegistrationType\Models\GstRegistrationType;

class GstRegistrationTypeService extends BaseService implements GstRegistrationTypeServiceInterface
{
    protected string $modelClass = GstRegistrationType::class;

    protected string $repositoryFacadeClass = GstRegistrationTypeRepositoryFacade::class;

    public function __construct() {}
}
