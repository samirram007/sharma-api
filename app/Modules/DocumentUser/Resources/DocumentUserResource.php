<?php

namespace Modules\DocumentUser\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class DocumentUserResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

        ]);

    }
}
