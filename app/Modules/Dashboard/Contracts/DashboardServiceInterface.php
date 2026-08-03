<?php

namespace Modules\Dashboard\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface DashboardServiceInterface extends BaseServiceInterface
{
    /**
     * Aggregated dashboard summary (stat card data).
     *
     * @return array<string, mixed>
     */
    public function summary(): array;

    public function zoneWise(): Collection;

    public function godownWise(): Collection;

    public function transporterWise(): Collection;

    public function userWise(): Collection;
}
