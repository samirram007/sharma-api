<?php

namespace Modules\ConversionJournalReport\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ConversionJournalReportServiceInterface extends BaseServiceInterface
{
    public function getAll(): LengthAwarePaginator;

    public function getGroupedByStockItem(array $params = []): Collection;

    public function getGroupedByGodown(array $params = []): Collection;

    public function getGroupedByDate(array $params = []): Collection;
}
