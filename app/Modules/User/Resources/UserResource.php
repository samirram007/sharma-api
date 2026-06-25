<?php

namespace App\Modules\User\Resources;

use App\Modules\Role\Resources\RoleResource;
use App\Modules\UserFiscalYear\Resources\UserFiscalYearResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'userType' => $this->user_type,
            'role' => $this->user_type,
            'status' => $this->status,
            'userFiscalYear' => UserFiscalYearResource::make($this->whenLoaded('user_fiscal_year')),
            // 'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'roleIds' => $this->whenLoaded(
                'roles',
                fn() => $this->roles->pluck('id')->values()
            ),
        ];
    }
}
