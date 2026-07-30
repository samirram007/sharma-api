<?php

namespace Modules\AppModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AppModuleFeature\Models\AppModuleFeature;

class AppModule extends Model
{
    use HasFactory;

    protected $table = 'app_modules';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'icon',
    ];

    protected $casts = [
    ];

    public function app_module_features(): HasMany
    {
        return $this->hasMany(AppModuleFeature::class, 'app_module_id');
    }
}
