<?php

namespace Modules\ManufacturingJournalReport\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ManufacturingJournalReportServiceInterface extends BaseServiceInterface
{
    public function getAll(): LengthAwarePaginator;

    public function getGroupedByStockItem(array $params = []): Collection;

    public function getGroupedByGodown(array $params = []): Collection;

    public function getGroupedByDate(array $params = []): Collection;
}
