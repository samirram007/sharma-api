<?php

namespace Modules\FiscalYear\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Company\Resources\CompanyResource;

class FiscalYearResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'startDate' => $this->start_date?->format('Y-m-d'),
            'endDate' => $this->end_date?->format('Y-m-d'),
            'companyId' => $this->company_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'status' => $this->status,
            'closedAt' => $this->closed_at?->toISOString(),
            'closedBy' => $this->closed_by,

        ]);

    }
}
