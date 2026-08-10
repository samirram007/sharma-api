<?php

namespace Modules\AccountNature\Services;

use App\Support\Services\BaseService;
use Modules\AccountNature\Contracts\AccountNatureServiceInterface;

use Modules\AccountNature\Models\AccountNature;

class AccountNatureService extends BaseService implements AccountNatureServiceInterface
{
    protected string $modelClass = AccountNature::class;

    public function __construct() {}
}
