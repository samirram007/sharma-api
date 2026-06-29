<?php

namespace App\Modules\PhysicalStockCount\Models;

use App\Modules\FiscalYear\Models\FiscalYear;
use App\Modules\Godown\Models\Godown;
use App\Modules\PhysicalStockCount\Models\PhysicalStockCountItem;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysicalStockCount extends Model
{
    use HasFactory;

    protected $table = 'physical_stock_counts';

    protected $fillable = [
        'fiscal_year_id',
        'godown_id',
        'count_date',
        'status',
        'counted_by',
        'remarks',
    ];

    protected $casts = [
        'count_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('orderByDate', function (Builder $builder) {
            $builder->orderBy('count_date', 'desc');
        });
    }

    public function fiscal_year(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function godown(): BelongsTo
    {
        return $this->belongsTo(Godown::class);
    }

    public function counted_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PhysicalStockCountItem::class, 'physical_stock_count_id');
    }

    public function totalItems(): int
    {
        return $this->items()->count();
    }

    public function totalDifference(): float
    {
        return (float) $this->items()->sum('difference');
    }

    public function totalDifferenceValue(): float
    {
        return (float) $this->items()->sum(DB::raw('difference * rate'));
    }
}
