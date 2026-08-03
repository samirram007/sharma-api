<?php

namespace Modules\DeliveryRoute\Services;

use App\Support\Services\BaseService;
use Modules\DeliveryRoute\Contracts\DeliveryRouteServiceInterface;
use Modules\DeliveryRoute\Facades\DeliveryRouteRepositoryFacade;
use Modules\DeliveryRoute\Models\DeliveryRoute;

class DeliveryRouteService extends BaseService implements DeliveryRouteServiceInterface
{
    protected string $modelClass = DeliveryRoute::class;

    protected array $defaultResource = [
        'source_place',
        'destination_place',
        'transporter',
        'rate_unit',
    ];

    protected string $repositoryFacadeClass = DeliveryRouteRepositoryFacade::class;

    public function __construct() {}
}
