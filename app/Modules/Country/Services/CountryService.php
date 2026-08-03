<?php

namespace Modules\Country\Services;

use App\Support\Services\BaseService;
use Modules\Country\Contracts\CountryServiceInterface;
use Modules\Country\Facades\CountryRepositoryFacade;
use Modules\Country\Models\Country;

class CountryService extends BaseService implements CountryServiceInterface
{
    protected string $modelClass = Country::class;

    protected array $defaultResource = ['states'];

    protected string $repositoryFacadeClass = CountryRepositoryFacade::class;

    public function __construct() {}
}
