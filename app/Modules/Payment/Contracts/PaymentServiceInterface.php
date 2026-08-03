<?php

namespace Modules\Payment\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface PaymentServiceInterface extends BaseServiceInterface
{
    public function getPaymentsByFreightId(int $freight_id): Collection;
}
