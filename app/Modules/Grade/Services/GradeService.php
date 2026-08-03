<?php

namespace Modules\Grade\Services;

use App\Support\Services\BaseService;
use Modules\Grade\Contracts\GradeServiceInterface;
use Modules\Grade\Facades\GradeRepositoryFacade;
use Modules\Grade\Models\Grade;

class GradeService extends BaseService implements GradeServiceInterface
{
    protected string $modelClass = Grade::class;

    protected string $repositoryFacadeClass = GradeRepositoryFacade::class;

    public function __construct() {}
}
