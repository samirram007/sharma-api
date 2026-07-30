<?php

namespace Modules\StockJournalStorageUnitEntry\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\StorageUnit\Resources\StorageUnitResource;

class StockJournalStorageUnitEntryResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'stockJournalEntryId' => $this->stock_journal_entry_id,
            'storageUnitId' => $this->storage_unit_id,
            'batchNo' => $this->batch_no,
            'mfgDate' => $this->mfg_date,
            'expiryDate' => $this->expiry_date,
            'serialNo' => $this->serial_no,
            'actualQuantity' => $this->actual_quantity,
            'billingQuantity' => $this->billing_quantity,
            'rate' => $this->rate,
            'discountPercentage' => $this->discount_percentage,
            'discount' => $this->discount,
            'amount' => $this->amount,
            'movementType' => $this->movement_type,
            'remarks' => $this->remarks,
            'storageUnit' => StorageUnitResource::make($this->whenLoaded('storage_unit')),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),

        ]);

    }
}
