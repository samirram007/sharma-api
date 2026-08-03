<?php

namespace Modules\Address\Services;

use App\Support\Services\BaseService;
use Modules\Address\Contracts\AddressServiceInterface;
use Modules\Address\Facades\AddressRepositoryFacade;
use Modules\Address\Models\Address;

class AddressService extends BaseService implements AddressServiceInterface
{
    protected string $modelClass = Address::class;

    protected string $repositoryFacadeClass = AddressRepositoryFacade::class;

    public function __construct() {}
}
