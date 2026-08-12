<?php

namespace Modules\ReceiptNoteReport\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReceiptNoteReportServiceInterface extends BaseServiceInterface
{
    public function getAll(): LengthAwarePaginator;

    public function getGroupedByLedger(array $params = []): Collection;

    public function getGroupedByDate(array $params = []): Collection;

    public function getGroupedByStockItem(array $params = []): Collection;

    public function getGroupedByGodown(array $params = []): Collection;
}
