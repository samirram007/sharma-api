<?php

namespace Modules\DayBook\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DayBookServiceInterface extends BaseServiceInterface
{
    public function dayBooksSelf(array $params = []): LengthAwarePaginator;

    public function getUsedVoucherTypes(): Collection;
}
