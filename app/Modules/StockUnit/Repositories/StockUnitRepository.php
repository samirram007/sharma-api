<?php

namespace Modules\StockUnit\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockUnit\Contracts\StockUnitRepositoryInterface;
use Modules\StockUnit\Models\StockUnit;

class StockUnitRepository extends BaseRepository implements StockUnitRepositoryInterface
{
    public function __construct(StockUnit $model)
    {
        parent::__construct($model);
    }
}
