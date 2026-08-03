<?php

namespace Modules\FiscalYear\Services;

use App\Support\Services\BaseService;
use Modules\FiscalYear\Contracts\FiscalYearServiceInterface;
use Modules\FiscalYear\Facades\FiscalYearRepositoryFacade;
use Modules\FiscalYear\Models\FiscalYear;

class FiscalYearService extends BaseService implements FiscalYearServiceInterface
{
    protected string $modelClass = FiscalYear::class;

    protected array $defaultResource = [
        'company',
    ];

    protected string $repositoryFacadeClass = FiscalYearRepositoryFacade::class;

    public function __construct() {}
}
