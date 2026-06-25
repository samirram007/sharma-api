<?php

namespace App\Modules\FiscalYear\Resources;

use App\Modules\Company\Resources\CompanyResource;
use Illuminate\Http\Request;

use App\Http\Resources\SuccessResource;
class FiscalYearResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'startDate' => $this->start_date?->format('Y-m-d'),
            'endDate' => $this->end_date?->format('Y-m-d'),
            'companyId' => $this->company_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'status' => $this->status

        ];
    }
}
