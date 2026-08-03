<?php

namespace Modules\Agent\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Agent\Contracts\AgentRepositoryInterface;
use Modules\Agent\Models\Agent;

class AgentRepository extends BaseRepository implements AgentRepositoryInterface
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

    public function __construct(Agent $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
