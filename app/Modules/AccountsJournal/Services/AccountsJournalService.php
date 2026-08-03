<?php

namespace Modules\AccountsJournal\Services;

use App\Support\Services\BaseService;
use Modules\AccountsJournal\Contracts\AccountsJournalServiceInterface;
use Modules\AccountsJournal\Facades\AccountsJournalRepositoryFacade;
use Modules\AccountsJournal\Models\AccountsJournal;

class AccountsJournalService extends BaseService implements AccountsJournalServiceInterface
{
    protected string $modelClass = AccountsJournal::class;

    protected string $repositoryFacadeClass = AccountsJournalRepositoryFacade::class;

    public function __construct() {}
}
