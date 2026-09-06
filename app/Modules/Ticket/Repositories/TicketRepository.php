<?php

namespace Modules\Ticket\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Ticket\Contracts\TicketRepositoryInterface;
use Modules\Ticket\Models\Ticket;

class TicketRepository extends BaseRepository implements TicketRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'subject', 'description', 'category',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status', 'priority', 'category', 'created_by', 'assigned_to',
    ];

    public function __construct(Ticket $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
