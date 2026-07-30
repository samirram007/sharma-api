<?php

namespace Modules\StockJournal\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\StockJournalEntry\Resources\StockJournalEntryResource;

class StockJournalResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'journalNo' => $this->journal_no,
            'journalDate' => $this->journal_date,
            'voucherId' => $this->voucher_id,
            'type' => $this->type,
            'remarks' => $this->remarks,
            'stockJournalEntries' => StockJournalEntryResource::collection($this->whenLoaded('stock_journal_entries')),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),

        ]);

    }
}
