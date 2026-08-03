<?php

namespace Modules\DocumentUser\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\DocumentUser\Contracts\DocumentUserRepositoryInterface;
use Modules\DocumentUser\Models\DocumentUser;

class DocumentUserRepository extends BaseRepository implements DocumentUserRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(DocumentUser $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
