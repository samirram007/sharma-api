<?php

namespace App\Modules\CompanyType\Resources;

use App\Http\Resources\SuccessResource;
use App\Modules\Company\Resources\CompanyCollection;
use Illuminate\Http\Request;


class CompanyTypeResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'companies' => new CompanyCollection($this->whenLoaded('companies')),
        ];
    }
}
