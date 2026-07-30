<?php

namespace Modules\Branch\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Branch\Contracts\BranchRepositoryInterface;
use Modules\Branch\Models\Branch;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }
}
