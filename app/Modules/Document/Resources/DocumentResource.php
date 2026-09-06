<?php

namespace Modules\Document\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class DocumentResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'userId' => $this->user_id,
            'companyId' => $this->company_id,
            'branchId' => $this->branch_id,
            'fiscalYearId' => $this->fiscal_year_id,
            'voucherId' => $this->voucher_id,
            'documentTypeId' => $this->document_type_id,
            'documentStatusId' => $this->document_status_id,
            'link' => $this->link,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ]);
    }
}
