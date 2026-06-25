<?php

namespace App\Modules\AppNotification\Resources;

use App\Http\Resources\SuccessResource;
use Illuminate\Http\Request;

class AppNotificationResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'relatedEntityType' => $this->related_entity_type,
            'relatedEntityId' => $this->related_entity_id,
            'voucherId' => $this->voucher_id,
            'field' => $this->field,
            'userId' => $this->user_id,
            'isRead' => $this->is_read,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
