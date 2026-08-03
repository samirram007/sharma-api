<?php

namespace Modules\StockJournalEntryPurge\Services;

use App\Support\Services\BaseService;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeServiceInterface;
use Modules\StockJournalEntryPurge\Facades\StockJournalEntryPurgeRepositoryFacade;
use Modules\StockJournalEntryPurge\Models\StockJournalEntryPurge;

class StockJournalEntryPurgeService extends BaseService implements StockJournalEntryPurgeServiceInterface
{
    protected string $modelClass = StockJournalEntryPurge::class;

    protected string $repositoryFacadeClass = StockJournalEntryPurgeRepositoryFacade::class;

    public function __construct() {}
}
