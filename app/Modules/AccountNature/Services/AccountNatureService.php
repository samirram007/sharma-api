<?php

namespace Modules\AccountNature\Services;

use App\Support\Services\BaseService;
use Modules\AccountNature\Contracts\AccountNatureServiceInterface;
use Modules\AccountNature\Facades\AccountNatureRepositoryFacade;
use Modules\AccountNature\Models\AccountNature;

class AccountNatureService extends BaseService implements AccountNatureServiceInterface
{
    protected string $modelClass = AccountNature::class;

    protected string $repositoryFacadeClass = AccountNatureRepositoryFacade::class;

    public function __construct() {}
}
