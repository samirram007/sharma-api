<?php

namespace Modules\Receipt\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Receipt\Contracts\ReceiptServiceInterface;

class ReceiptFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ReceiptServiceInterface::class;
    }
}
