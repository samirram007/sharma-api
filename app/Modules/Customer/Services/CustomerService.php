<?php

namespace Modules\Customer\Services;

use App\Support\Services\BaseService;
use Modules\Customer\Contracts\CustomerServiceInterface;
use Modules\Customer\Models\Customer;

class CustomerService extends BaseService implements CustomerServiceInterface
{
    protected string $modelClass = Customer::class;

    protected array $defaultResource = [];
}
