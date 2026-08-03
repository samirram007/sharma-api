<?php

namespace Modules\VoucherParty\Services;

use App\Support\Services\BaseService;
use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;
use Modules\VoucherParty\Facades\VoucherPartyRepositoryFacade;
use Modules\VoucherParty\Models\VoucherParty;

class VoucherPartyService extends BaseService implements VoucherPartyServiceInterface
{
    protected string $modelClass = VoucherParty::class;

    protected string $repositoryFacadeClass = VoucherPartyRepositoryFacade::class;

    public function __construct() {}
}
