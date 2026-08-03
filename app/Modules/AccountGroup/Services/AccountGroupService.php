<?php

namespace Modules\AccountGroup\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AccountGroup\Contracts\AccountGroupServiceInterface;
use Modules\AccountGroup\Facades\AccountGroupRepositoryFacade;
use Modules\AccountGroup\Models\AccountGroup;

class AccountGroupService extends BaseService implements AccountGroupServiceInterface
{
    protected string $modelClass = AccountGroup::class;

    protected array $defaultResource = ['account_nature'];

    public function __construct() {}

    public function getCurrentLiabilityGroups(): Collection
    {
        return AccountGroupRepositoryFacade::getCurrentLiabilityGroups();
    }
}
