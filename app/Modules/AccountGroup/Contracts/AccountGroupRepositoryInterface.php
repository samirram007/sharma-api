<?php

namespace Modules\AccountGroup\Contracts;

use App\Support\Contracts\BaseRepositoryInterface;

interface AccountGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get current liability groups.
     */
    public function getCurrentLiabilityGroups(): mixed;
}
