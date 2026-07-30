<?php

namespace Modules\Country\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Country\Contracts\CountryRepositoryInterface;
use Modules\Country\Models\Country;

class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }
}
