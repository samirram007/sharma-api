<?php

namespace Modules\UserFiscalYear\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\FiscalYear\Resources\FiscalYearResource;
use Modules\User\Resources\UserResource;

class UserFiscalYearResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'userId' => $this->user_id,
            // MySQL returns fiscal_year_id as a string unless the model casts
            // it; strict === comparisons against numeric ids (voucher
            // fiscalYearId, fiscal year rows) would silently fail. Emit a
            // real int so the frontend can compare ids reliably.
            'fiscalYearId' => (int) $this->fiscal_year_id,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'currentDate' => $this->current_date ?? now(),
            'user' => UserResource::make($this->whenLoaded('user')),
            'fiscalYear' => FiscalYearResource::make($this->whenLoaded('fiscal_year')),

        ]);

    }
}
