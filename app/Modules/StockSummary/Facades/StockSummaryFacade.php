<?php

namespace Modules\StockSummary\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;

class StockSummaryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockSummaryServiceInterface::class;
    }
}
