<?php

namespace Modules\AccountNature\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AccountNature\Contracts\AccountNatureRepositoryInterface;
use Modules\AccountNature\Models\AccountNature;

class AccountNatureRepository extends BaseRepository implements AccountNatureRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
        'icon',
        // 'accounting_effect',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(AccountNature $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
