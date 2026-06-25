<?php

namespace App\Modules\Status\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\SuccessResource;

class StatusResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
        ];
    }
}
