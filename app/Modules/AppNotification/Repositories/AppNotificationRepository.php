<?php

namespace Modules\AppNotification\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\AppNotification\Contracts\AppNotificationRepositoryInterface;
use Modules\AppNotification\Models\AppNotification;

class AppNotificationRepository extends BaseRepository implements AppNotificationRepositoryInterface
{
    protected array $searchableFields = [
        // 'type',
        // 'title',
        // 'message',
    ];

    protected array $filterableFields = [
        // 'is_read',
        // 'user_id',
        // 'voucher_id',
        // 'related_entity_type',
    ];

    public function __construct(AppNotification $model)
    {
        parent::__construct($model);
    }
}
