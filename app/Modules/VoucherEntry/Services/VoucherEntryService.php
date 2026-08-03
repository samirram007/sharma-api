<?php

namespace Modules\VoucherEntry\Services;

use App\Support\Services\BaseService;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherEntry\Facades\VoucherEntryRepositoryFacade;
use Modules\VoucherEntry\Models\VoucherEntry;

class VoucherEntryService extends BaseService implements VoucherEntryServiceInterface
{
    protected string $modelClass = VoucherEntry::class;

    protected array $defaultResource = [
        'account_ledger',
    ];

    protected string $repositoryFacadeClass = VoucherEntryRepositoryFacade::class;

    public function __construct() {}
}
