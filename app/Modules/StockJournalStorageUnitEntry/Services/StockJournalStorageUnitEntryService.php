<?php

namespace Modules\StockJournalStorageUnitEntry\Services;

use App\Support\Services\BaseService;
use Modules\StockJournalStorageUnitEntry\Contracts\StockJournalStorageUnitEntryServiceInterface;
use Modules\StockJournalStorageUnitEntry\Facades\StockJournalStorageUnitEntryRepositoryFacade;
use Modules\StockJournalStorageUnitEntry\Models\StockJournalStorageUnitEntry;

class StockJournalStorageUnitEntryService extends BaseService implements StockJournalStorageUnitEntryServiceInterface
{
    protected string $modelClass = StockJournalStorageUnitEntry::class;

    protected string $repositoryFacadeClass = StockJournalStorageUnitEntryRepositoryFacade::class;

    public function __construct() {}
}
