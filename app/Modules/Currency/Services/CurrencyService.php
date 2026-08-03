<?php

namespace Modules\Currency\Services;

use App\Support\Services\BaseService;
use Modules\Currency\Contracts\CurrencyServiceInterface;
use Modules\Currency\Facades\CurrencyRepositoryFacade;
use Modules\Currency\Models\Currency;

class CurrencyService extends BaseService implements CurrencyServiceInterface
{
    protected string $modelClass = Currency::class;

    protected string $repositoryFacadeClass = CurrencyRepositoryFacade::class;

    public function __construct() {}
}
