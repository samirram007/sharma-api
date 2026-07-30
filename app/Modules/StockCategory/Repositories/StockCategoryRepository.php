<?php

namespace Modules\StockCategory\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\StockCategory\Contracts\StockCategoryRepositoryInterface;
use Modules\StockCategory\Models\StockCategory;

class StockCategoryRepository extends BaseRepository implements StockCategoryRepositoryInterface
{
    public function __construct(StockCategory $model)
    {
        parent::__construct($model);
    }
}
