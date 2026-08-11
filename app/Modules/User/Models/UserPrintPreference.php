<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrintPreference extends Model
{
    protected $table = 'user_print_preferences';

    protected $fillable = [
        'user_id',
        'show_fare_details',
        'show_document_info',
        'show_authorizations',
        'show_paid_to_amount',
    ];

    protected $casts = [
        'show_fare_details' => 'boolean',
        'show_document_info' => 'boolean',
        'show_authorizations' => 'boolean',
        'show_paid_to_amount' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
