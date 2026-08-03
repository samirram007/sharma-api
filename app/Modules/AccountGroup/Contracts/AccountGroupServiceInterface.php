<?php

namespace Modules\AccountGroup\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface AccountGroupServiceInterface extends BaseServiceInterface
{
    public function getCurrentLiabilityGroups(): Collection;
}
