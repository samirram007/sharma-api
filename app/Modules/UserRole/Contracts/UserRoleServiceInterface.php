<?php

namespace Modules\UserRole\Contracts;

use App\Support\Contracts\BaseServiceInterface;

interface UserRoleServiceInterface extends BaseServiceInterface
{
    // Inherits BaseServiceInterface::store(array $data): Model — the previous
    // `store(): UserRole|bool|null` declaration was incompatible with the base
    // signature (return type widening) and caused a fatal error on interface load.
}
