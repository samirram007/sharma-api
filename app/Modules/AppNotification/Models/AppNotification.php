<?php

namespace Modules\AppNotification\Models;

use Modules\User\Models\User;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use Blameable;

    protected $table = 'app_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'related_entity_type',
        'related_entity_id',
        'voucher_id',
        'field',
        'user_id',
        'is_read',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }
}
