<?php

namespace Modules\User\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use App\Traits\HasPolymorphicResource;
use Illuminate\Http\Request;

use Modules\Role\Resources\RoleCollection;
use Modules\Role\Resources\RoleResource;
use Modules\UserFiscalYear\Resources\UserFiscalYearResource;

class UserResource extends SuccessResource
{
    use CamelCaseResource;
    use HasPolymorphicResource;

    // public function with(Request $request): array
    // {
    //     return [];
    // }

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'userType' => $this->user_type,
            'role' => $this->user_type,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'userFiscalYear' => UserFiscalYearResource::make($this->whenLoaded('user_fiscal_year')),
            'roles' => RoleCollection::make($this->whenLoaded('roles')),
            'roleIds' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->pluck('id')->values()
            ),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ]);
    }
}
