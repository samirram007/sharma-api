<?php

namespace Modules\AccountGroup\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AccountGroup\Contracts\AccountGroupRepositoryInterface;
use Modules\AccountGroup\Models\AccountGroup;

class AccountGroupRepository extends BaseRepository implements AccountGroupRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'account_nature_id',
        'status',
    ];

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
