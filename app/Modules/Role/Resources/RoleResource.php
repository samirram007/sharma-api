<?php

namespace Modules\Role\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\RolePermission\Resources\RolePermissionResource;

class RoleResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'permissions' => RolePermissionResource::collection($this->whenLoaded('permissions')),
        ]);
    }
}
