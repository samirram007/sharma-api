<?php

namespace Modules\AppModuleFeature\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AppModule\Resources\AppModuleResource;
use Modules\RolePermission\Resources\RolePermissionResource;

class AppModuleFeatureResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'description' => $this->description,
            'action' => $this->action,
            'icon' => $this->icon,
            'appModuleId' => $this->app_module_id,
            'appModule' => $this->when(
                $this->relationLoaded('module') && $this->module,
                fn () => AppModuleResource::make($this->module)
            ),
            'rolePermission' => $this->when(
                $this->relationLoaded('role_permissions') && $this->role_permissions->isNotEmpty(),
                fn () => RolePermissionResource::make($this->role_permissions->first())
            ),
            'rolePermissions' => RolePermissionResource::collection($this->whenLoaded('role_permissions')),

        ]);

    }
}
