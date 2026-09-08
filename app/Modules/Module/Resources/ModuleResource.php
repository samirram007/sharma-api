<?php

namespace Modules\Module\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class ModuleResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'id' => $this->id,
            'name' => $this->name,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ]);
    }
}
