<?php

namespace Modules\GstRegistrationType\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GstRegistrationType extends Model
{
    use HasFactory;

    protected $table = 'gst_registration_types';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'status',

    ];

    protected $casts = [
    ];
}
