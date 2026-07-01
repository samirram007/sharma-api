<?php

namespace Modules\TestItem\Models;

use Modules\StockItem\Models\StockItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TestItem extends StockItem
{
    use HasFactory;

    protected $casts = [];



}
