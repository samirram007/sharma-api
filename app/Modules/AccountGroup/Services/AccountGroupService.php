<?php

namespace Modules\AccountGroup\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AccountGroup\Contracts\AccountGroupRepositoryInterface;
use Modules\AccountGroup\Contracts\AccountGroupServiceInterface;
use Modules\AccountGroup\Models\AccountGroup;

class AccountGroupService extends BaseService implements AccountGroupServiceInterface
{
    protected string $modelClass = AccountGroup::class;

    protected array $defaultResource = ['account_nature'];

    public function __construct(
        protected AccountGroupRepositoryInterface $accountGroupRepository
    ) {}

    /**
     * Override to provide covariant return type matching the interface.
     */
    public function getById(int $id): ?AccountGroup
    {
        return parent::getById($id);
    }

    /**
     * Override to provide covariant return type matching the interface.
     */
    public function store(array $data): AccountGroup
    {
        return parent::store($data);
    }

    /**
     * Override to provide covariant return type matching the interface.
     */
    public function update(array $data, int $id): AccountGroup
    {
        return parent::update($data, $id);
    }

    public function getCurrentLiabilityGroups(): Collection
    {
        return $this->accountGroupRepository->getCurrentLiabilityGroups();
    }
}
