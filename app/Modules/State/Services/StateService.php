<?php

namespace Modules\State\Services;

use App\Support\Services\BaseService;
use Modules\State\Contracts\StateServiceInterface;
use Modules\State\Facades\StateRepositoryFacade;
use Modules\State\Models\State;

class StateService extends BaseService implements StateServiceInterface
{
    protected string $modelClass = State::class;

    protected array $defaultResource = [
        'country',
    ];

    protected string $repositoryFacadeClass = StateRepositoryFacade::class;

    public function __construct() {}
}
