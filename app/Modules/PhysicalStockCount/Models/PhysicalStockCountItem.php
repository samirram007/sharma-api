<?php

namespace Modules\PhysicalStockCount\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\StockItem\Models\StockItem;

class PhysicalStockCountItem extends Model
{
    use HasFactory;

    protected $table = 'physical_stock_count_items';

    protected $fillable = [
        'physical_stock_count_id',
        'stock_item_id',
        'batch_no',
        'serial_no',
        'mfg_date',
        'expiry_date',
        'system_quantity',
        'physical_quantity',
        'rate',
        'remarks',
        'entry_order',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'physical_quantity' => 'decimal:4',
        'rate' => 'decimal:2',
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'difference' => 'decimal:4',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function physical_stock_count(): BelongsTo
    {
        return $this->belongsTo(PhysicalStockCount::class);
    }

    public function stock_item(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
