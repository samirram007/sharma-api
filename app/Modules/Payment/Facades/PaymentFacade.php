<?php

namespace Modules\Payment\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Payment\Contracts\PaymentServiceInterface;

class PaymentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentServiceInterface::class;
    }
}
