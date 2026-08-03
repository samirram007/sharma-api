<?php

namespace Modules\StorageUnit\Services;

use App\Support\Services\BaseService;
use Modules\StorageUnit\Contracts\StorageUnitServiceInterface;
use Modules\StorageUnit\Facades\StorageUnitRepositoryFacade;
use Modules\StorageUnit\Models\StorageUnit;

class StorageUnitService extends BaseService implements StorageUnitServiceInterface
{
    protected string $modelClass = StorageUnit::class;

    protected array $defaultResource = [
        'parent',
        'capacity_unit',
        'address',
    ];

    protected string $repositoryFacadeClass = StorageUnitRepositoryFacade::class;

    public function __construct() {}
}
