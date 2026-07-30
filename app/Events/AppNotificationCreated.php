<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AppNotification\Models\AppNotification;

class AppNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The notification instance.
     */
    public AppNotification $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(AppNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $userId = $this->notification->user_id;

        if ($userId) {
            return [
                new PrivateChannel('user.notifications.'.$userId),
            ];
        }

        return [];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'relatedEntityType' => $this->notification->related_entity_type,
            'relatedEntityId' => $this->notification->related_entity_id,
            'voucherId' => $this->notification->voucher_id,
            'field' => $this->notification->field,
            'userId' => $this->notification->user_id,
            'isRead' => $this->notification->is_read,
            'createdAt' => $this->notification->created_at,
        ];
    }
}
