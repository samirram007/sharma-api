<?php

namespace Modules\RolePermission\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AppModuleFeature\Resources\AppModuleFeatureResource;
use Modules\Role\Resources\RoleResource;

class RolePermissionResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'roleId' => $this->role_id,
            'appModuleFeatureId' => $this->app_module_feature_id,
            'isAllowed' => $this->is_allowed,
            'role' => $this->when(
                $this->relationLoaded('role') && $this->role,
                fn () => RoleResource::make($this->role)
            ),
            'appModuleFeature' => $this->when(
                $this->relationLoaded('feature') && $this->feature,
                fn () => AppModuleFeatureResource::make($this->feature)
            ),

        ]);

    }
}
