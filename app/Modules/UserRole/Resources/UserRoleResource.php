<?php

namespace Modules\UserRole\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Role\Resources\RoleResource;
use Modules\User\Resources\UserResource;

class UserRoleResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'userId' => $this->user_id,
            'roleId' => $this->role_id,
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn () => UserResource::make($this->user)
            ),
            'role' => $this->when(
                $this->relationLoaded('role') && $this->role,
                fn () => RoleResource::make($this->role)
            ),

        ]);

    }
}
