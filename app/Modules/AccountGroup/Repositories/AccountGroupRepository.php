<?php

namespace Modules\AccountGroup\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AccountGroup\Contracts\AccountGroupRepositoryInterface;
use Modules\AccountGroup\Models\AccountGroup;

class AccountGroupRepository extends BaseRepository implements AccountGroupRepositoryInterface
{
    public function __construct(AccountGroup $model)
    {
        parent::__construct($model);
    }

    public function getCurrentLiabilityGroups(): mixed
    {
        return $this->remember(
            $this->getCacheKey('currentLiabilityGroups'),
            fn () => $this->query()
                ->with(['account_nature'])
                ->where(function ($q) {
                    $q->where('id', 20002)
                        ->orWhere('parent_id', 20002);
                })
                ->orderBy('name')
                ->get()
        );
    }
}
