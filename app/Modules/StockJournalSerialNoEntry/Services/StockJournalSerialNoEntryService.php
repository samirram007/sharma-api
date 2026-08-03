<?php

namespace Modules\StockJournalSerialNoEntry\Services;

use App\Support\Services\BaseService;
use Modules\StockJournalSerialNoEntry\Contracts\StockJournalSerialNoEntryServiceInterface;
use Modules\StockJournalSerialNoEntry\Facades\StockJournalSerialNoEntryRepositoryFacade;
use Modules\StockJournalSerialNoEntry\Models\StockJournalSerialNoEntry;

class StockJournalSerialNoEntryService extends BaseService implements StockJournalSerialNoEntryServiceInterface
{
    protected string $modelClass = StockJournalSerialNoEntry::class;

    protected string $repositoryFacadeClass = StockJournalSerialNoEntryRepositoryFacade::class;

    public function __construct() {}
}
