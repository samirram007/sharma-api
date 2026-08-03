<?php

namespace Modules\Holiday\Services;

use App\Support\Services\BaseService;
use Modules\Holiday\Contracts\HolidayServiceInterface;
use Modules\Holiday\Facades\HolidayRepositoryFacade;
use Modules\Holiday\Models\Holiday;

class HolidayService extends BaseService implements HolidayServiceInterface
{
    protected string $modelClass = Holiday::class;

    protected string $repositoryFacadeClass = HolidayRepositoryFacade::class;

    public function __construct() {}
}
