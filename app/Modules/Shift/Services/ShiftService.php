<?php

namespace Modules\Shift\Services;

use App\Support\Services\BaseService;
use Modules\Shift\Contracts\ShiftServiceInterface;
use Modules\Shift\Facades\ShiftRepositoryFacade;
use Modules\Shift\Models\Shift;

class ShiftService extends BaseService implements ShiftServiceInterface
{
    protected string $modelClass = Shift::class;

    protected string $repositoryFacadeClass = ShiftRepositoryFacade::class;

    public function __construct() {}
}
