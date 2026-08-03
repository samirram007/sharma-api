<?php

namespace Modules\StockJournalGodownEntryPurge\Services;

use App\Support\Services\BaseService;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeServiceInterface;
use Modules\StockJournalGodownEntryPurge\Facades\StockJournalGodownEntryPurgeRepositoryFacade;
use Modules\StockJournalGodownEntryPurge\Models\StockJournalGodownEntryPurge;

class StockJournalGodownEntryPurgeService extends BaseService implements StockJournalGodownEntryPurgeServiceInterface
{
    protected string $modelClass = StockJournalGodownEntryPurge::class;

    protected string $repositoryFacadeClass = StockJournalGodownEntryPurgeRepositoryFacade::class;

    public function __construct() {}
}
