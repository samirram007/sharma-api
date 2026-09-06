<?php

namespace Modules\Ticket\Services;

use App\Support\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Modules\Ticket\Contracts\TicketServiceInterface;
use Modules\Ticket\Facades\TicketRepositoryFacade;
use Modules\Ticket\Models\Ticket;
use Modules\Ticket\Models\TicketResponse;

class TicketService extends BaseService implements TicketServiceInterface
{
    protected string $modelClass = Ticket::class;

    protected string $repositoryFacadeClass = TicketRepositoryFacade::class;

    protected array $defaultResource = ['creator:id,name', 'assignee:id,name', 'responses'];

    public function __construct() {}

    /**
     * Get a ticket by ID with relationships loaded.
     */
    public function getTicketWithDetails(int $id): Ticket
    {
        return Ticket::with(['creator:id,name', 'assignee:id,name', 'responses.user:id,name'])
            ->findOrFail($id);
    }

    /**
     * Add a response to a ticket.
     */
    public function addResponse(int $ticketId, array $data): TicketResponse
    {
        $ticket = Ticket::findOrFail($ticketId);

        return TicketResponse::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $data['message'],
        ]);
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(int $ticketId, string $status): Ticket
    {
        $ticket = Ticket::findOrFail($ticketId);
        $updateData = ['status' => $status];

        if ($status === Ticket::STATUS_RESOLVED || $status === Ticket::STATUS_CLOSED) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        return $ticket->fresh(['creator:id,name', 'assignee:id,name', 'responses.user:id,name']);
    }
}
