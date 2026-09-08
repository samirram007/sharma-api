<?php

namespace Modules\Ticket\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class TicketResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            'createdBy' => $this->created_by,
            'assignedTo' => $this->assigned_to,
            'companyId' => $this->company_id,
            'resolvedAt' => $this->resolved_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'responses' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($r) => [
                'id' => $r->id,
                'ticketId' => $r->ticket_id,
                'userId' => $r->user_id,
                'userName' => $r->user?->name,
                'message' => $r->message,
                'createdAt' => $r->created_at?->toISOString(),
                'updatedAt' => $r->updated_at?->toISOString(),
            ])->values()),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ]),
        ]);
    }
}
