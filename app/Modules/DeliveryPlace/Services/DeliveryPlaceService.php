<?php

namespace Modules\DeliveryPlace\Services;

use App\Support\Services\BaseService;
use Modules\DeliveryPlace\Contracts\DeliveryPlaceServiceInterface;
use Modules\DeliveryPlace\Facades\DeliveryPlaceRepositoryFacade;
use Modules\DeliveryPlace\Models\DeliveryPlace;

class DeliveryPlaceService extends BaseService implements DeliveryPlaceServiceInterface
{
    protected string $modelClass = DeliveryPlace::class;

    protected string $repositoryFacadeClass = DeliveryPlaceRepositoryFacade::class;

    public function __construct() {}
}
