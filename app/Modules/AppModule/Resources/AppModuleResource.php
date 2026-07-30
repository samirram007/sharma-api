<?php

namespace Modules\AppModule\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AppModuleFeature\Resources\AppModuleFeatureResource;

class AppModuleResource extends SuccessResource
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
            'icon' => $this->icon,
            'features' => AppModuleFeatureResource::collection($this->whenLoaded('app_module_features')),

        ]);

    }
}
