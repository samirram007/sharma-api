<?php

namespace Modules\FiscalYear\Models;

use App\Enums\ActiveInactive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Company\Models\Company;

/**
 * @property int $id
 * @property string $name
 * @property string $start_date
 * @property string $end_date
 * @property string $status
 * @property int $company_id
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 */
class FiscalYear extends Model
{
    use HasFactory;

    protected $table = 'fiscal_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'company_id',
        'assessment_year',
        'closed_at',
        'closed_by',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'status' => ActiveInactive::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isActive(): bool
    {
        return $this->status === ActiveInactive::Active;
    }
}
