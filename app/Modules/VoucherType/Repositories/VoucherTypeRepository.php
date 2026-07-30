<?php

namespace Modules\VoucherType\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\VoucherType\Contracts\VoucherTypeRepositoryInterface;
use Modules\VoucherType\Models\VoucherType;

class VoucherTypeRepository extends BaseRepository implements VoucherTypeRepositoryInterface
{
    public function __construct(VoucherType $model)
    {
        parent::__construct($model);
    }
}
