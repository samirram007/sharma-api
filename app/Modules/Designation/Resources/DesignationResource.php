<?php

namespace Modules\Designation\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class DesignationResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return $this->toCamelCaseArray($request);
    }
}
