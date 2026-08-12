<?php

namespace Modules\UserFiscalYear\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\User\Models\User;

/**
 * @property int $user_id
 * @property int $fiscal_year_id
 * @property-read FiscalYear $fiscal_year
 */
class UserFiscalYear extends Model
{
    use HasFactory;

    protected $table = 'user_fiscal_years';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'fiscal_year_id',
        'start_date',
        'end_date',
        'current_date',
    ];

    protected $casts = [
        // The PHPDoc above declares int for these — MySQL returns them as
        // strings without a cast, which silently broke strict comparisons
        // against numeric ids (e.g. opening_stock one-per-FY checks). Casting
        // here fixes the attribute everywhere, not just in the resource.
        'user_id' => 'integer',
        'fiscal_year_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'current_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function fiscal_year(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id', 'id');
    }
}
