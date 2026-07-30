<?php

namespace Modules\FiscalYear\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\FiscalYear\Contracts\FiscalYearRepositoryInterface;
use Modules\FiscalYear\Models\FiscalYear;

class FiscalYearRepository extends BaseRepository implements FiscalYearRepositoryInterface
{
    public function __construct(FiscalYear $model)
    {
        parent::__construct($model);
    }
}
