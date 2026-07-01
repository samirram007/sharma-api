<?php

namespace Modules\AppNotification\Resources;

use App\Http\Resources\SuccessCollection;

class AppNotificationCollection extends SuccessCollection
{
    public function __construct($resource, string $message = null)
    {
        parent::__construct(
            AppNotificationResource::collection($resource),
            $message ?? 'Notifications fetched successfully'
        );
    }
}
