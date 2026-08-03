<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Services;

use App\Support\Services\BaseService;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeServiceInterface;
use Modules\StockJournalStorageUnitEntryPurge\Facades\StockJournalStorageUnitEntryPurgeRepositoryFacade;
use Modules\StockJournalStorageUnitEntryPurge\Models\StockJournalStorageUnitEntryPurge;

class StockJournalStorageUnitEntryPurgeService extends BaseService implements StockJournalStorageUnitEntryPurgeServiceInterface
{
    protected string $modelClass = StockJournalStorageUnitEntryPurge::class;

    protected string $repositoryFacadeClass = StockJournalStorageUnitEntryPurgeRepositoryFacade::class;

    public function __construct() {}
}
