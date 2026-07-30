<?php

namespace Modules\DeliveryVehicle\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Transporter\Models\Transporter;

class DeliveryVehicle extends Model
{
    use HasFactory;

    protected $table = 'delivery_vehicles';

    protected $fillable = [
        'transporter_id',
        'vehicle_type',
        'vehicle_number',
        'capacity',
        'driver_name',
        'driver_contact',
        'description',
        'status',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class, 'transporter_id', 'id');
    }
}
