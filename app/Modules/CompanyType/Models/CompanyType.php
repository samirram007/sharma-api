<?php

namespace Modules\CompanyType\Models;

use App\Enums\ActiveInactive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\Models\Company;

class CompanyType extends Model
{
    use HasFactory;

    protected $table = 'company_types';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => ActiveInactive::class,
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
