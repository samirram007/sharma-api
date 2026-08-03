<?php

namespace Modules\OrderStockJournal\Services;

use App\Support\Services\BaseService;
use Modules\OrderStockJournal\Contracts\OrderStockJournalServiceInterface;
use Modules\OrderStockJournal\Facades\OrderStockJournalRepositoryFacade;
use Modules\OrderStockJournal\Models\OrderStockJournal;

class OrderStockJournalService extends BaseService implements OrderStockJournalServiceInterface
{
    protected string $modelClass = OrderStockJournal::class;

    protected string $repositoryFacadeClass = OrderStockJournalRepositoryFacade::class;

    public function __construct() {}
}
