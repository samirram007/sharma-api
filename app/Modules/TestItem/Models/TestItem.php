<?php

namespace Modules\TestItem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\StockItem\Models\StockItem;

class TestItem extends StockItem
{
    use HasFactory;

    protected $casts = [];
}
