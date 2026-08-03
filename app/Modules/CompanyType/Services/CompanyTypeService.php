<?php

namespace Modules\CompanyType\Services;

use App\Support\Services\BaseService;
use Modules\CompanyType\Contracts\CompanyTypeServiceInterface;
use Modules\CompanyType\Facades\CompanyTypeRepositoryFacade;
use Modules\CompanyType\Models\CompanyType;

class CompanyTypeService extends BaseService implements CompanyTypeServiceInterface
{
    protected string $modelClass = CompanyType::class;

    protected array $defaultResource = [
        'companies',
    ];

    protected string $repositoryFacadeClass = CompanyTypeRepositoryFacade::class;

    public function __construct() {}
}
