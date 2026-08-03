<?php

namespace Modules\DocumentUser\Services;

use App\Support\Services\BaseService;
use Modules\DocumentUser\Contracts\DocumentUserServiceInterface;
use Modules\DocumentUser\Facades\DocumentUserRepositoryFacade;
use Modules\DocumentUser\Models\DocumentUser;

class DocumentUserService extends BaseService implements DocumentUserServiceInterface
{
    protected string $modelClass = DocumentUser::class;

    protected string $repositoryFacadeClass = DocumentUserRepositoryFacade::class;

    public function __construct() {}
}
