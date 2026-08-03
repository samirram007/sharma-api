<?php

namespace Modules\StockJournalBatchEntry\Services;

use App\Support\Services\BaseService;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryServiceInterface;
use Modules\StockJournalBatchEntry\Facades\StockJournalBatchEntryRepositoryFacade;
use Modules\StockJournalBatchEntry\Models\StockJournalBatchEntry;

class StockJournalBatchEntryService extends BaseService implements StockJournalBatchEntryServiceInterface
{
    protected string $modelClass = StockJournalBatchEntry::class;

    protected string $repositoryFacadeClass = StockJournalBatchEntryRepositoryFacade::class;

    public function __construct() {}
}
