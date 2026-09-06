<?php

namespace Modules\Ticket\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ticket\Facades\TicketFacade;
use Modules\Ticket\Requests\TicketRequest;
use Modules\Ticket\Requests\TicketResponseRequest;
use Modules\Ticket\Resources\TicketCollection;
use Modules\Ticket\Resources\TicketResource;
use Modules\Ticket\Services\TicketService;

class TicketController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly TicketService $ticketService) {}

    public function index(): SuccessCollection
    {
        $data = TicketFacade::getAll();

        return new TicketCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = $this->ticketService->getTicketWithDetails($id);

        return new TicketResource($data);
    }

    public function store(TicketRequest $request): SuccessResource
    {
        $data = TicketFacade::store($request->validated());

        return new TicketResource($data, 'Ticket created successfully');
    }

    public function update(TicketRequest $request, int $id): SuccessResource
    {
        $data = TicketFacade::update($request->validated(), $id);

        return new TicketResource($data, 'Ticket updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(TicketFacade::delete($id), 'Ticket');
    }

    /**
     * POST /api/tickets/{ticket}/responses
     */
    public function addResponse(TicketResponseRequest $request, int $ticket): JsonResponse
    {
        $response = $this->ticketService->addResponse($ticket, $request->validated());

        return $this->successResponse(
            [
                'id' => $response->id,
                'ticketId' => $response->ticket_id,
                'userId' => $response->user_id,
                'message' => $response->message,
                'createdAt' => $response->created_at?->toISOString(),
            ],
            'Response added successfully',
            201
        );
    }

    /**
     * PATCH /api/tickets/{ticket}/status
     */
    public function updateStatus(Request $request, int $ticket): SuccessResource
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticketModel = $this->ticketService->updateStatus($ticket, $data['status']);

        return new TicketResource($ticketModel, 'Ticket status updated successfully');
    }
}
