<?php

namespace Modules\VoucherEntryPurge\Services;

use App\Support\Services\BaseService;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeServiceInterface;
use Modules\VoucherEntryPurge\Facades\VoucherEntryPurgeRepositoryFacade;
use Modules\VoucherEntryPurge\Models\VoucherEntryPurge;

class VoucherEntryPurgeService extends BaseService implements VoucherEntryPurgeServiceInterface
{
    protected string $modelClass = VoucherEntryPurge::class;

    protected string $repositoryFacadeClass = VoucherEntryPurgeRepositoryFacade::class;

    public function __construct() {}
}
