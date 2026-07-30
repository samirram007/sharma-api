<?php

namespace Modules\StockGroup\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockGroup\Contracts\StockGroupRepositoryInterface;
use Modules\StockGroup\Models\StockGroup;

class StockGroupRepository extends BaseRepository implements StockGroupRepositoryInterface
{
    public function __construct(StockGroup $model)
    {
        parent::__construct($model);
    }
}
