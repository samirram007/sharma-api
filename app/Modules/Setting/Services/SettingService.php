<?php

namespace Modules\Setting\Services;

use App\Support\Services\BaseService;
use Modules\Setting\Contracts\SettingServiceInterface;
use Modules\Setting\Facades\SettingRepositoryFacade;
use Modules\Setting\Models\Setting;

class SettingService extends BaseService implements SettingServiceInterface
{
    protected string $modelClass = Setting::class;

    protected string $repositoryFacadeClass = SettingRepositoryFacade::class;

    public function __construct() {}
}
