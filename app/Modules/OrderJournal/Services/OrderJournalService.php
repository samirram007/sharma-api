<?php

namespace Modules\OrderJournal\Services;

use App\Support\Services\BaseService;
use Modules\OrderJournal\Contracts\OrderJournalServiceInterface;
use Modules\OrderJournal\Facades\OrderJournalRepositoryFacade;
use Modules\OrderJournal\Models\OrderJournal;

class OrderJournalService extends BaseService implements OrderJournalServiceInterface
{
    protected string $modelClass = OrderJournal::class;

    protected string $repositoryFacadeClass = OrderJournalRepositoryFacade::class;

    public function __construct() {}
}
