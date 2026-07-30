<?php

namespace Modules\GstRegistrationType\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class GstRegistrationTypeResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return $this->toCamelCaseArray($request);
    }
}
