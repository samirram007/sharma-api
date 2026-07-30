<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Address\Models\Address;
use Modules\User\Models\User;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
