<?php

namespace App\Modules\PhysicalStockCount\Resources;

use App\Http\Resources\SuccessCollection;

class PhysicalStockCountCollection extends SuccessCollection
{
    public $collects = PhysicalStockCountResource::class;
}
