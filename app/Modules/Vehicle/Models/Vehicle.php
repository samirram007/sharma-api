<?php

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Transporter\Models\Transporter;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'transporter_id',
        'vehicle_type',
        'vehicle_no',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class, 'transporter_id');
    }
}
