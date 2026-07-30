<?php

namespace Modules\Supplier\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Supplier\Contracts\SupplierRepositoryInterface;
use Modules\Supplier\Models\Supplier;

class SupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }
}
