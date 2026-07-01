<?php

namespace Modules\Menu\Resources;

use App\Http\Resources\SuccessResource;
use Illuminate\Http\Request;
use Modules\AppModuleFeature\Resources\AppModuleFeatureResource;

class MenuResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'menuId'    => $this->app_module_feature_id,
            'menuName'              => $this->menu_name,
            'route'                 => $this->route,
            'icon'                  => $this->icon,
            'parentId'              => $this->parent_id,
            'sortOrder'             => $this->sort_order,
            'status'                => $this->status,
            'isVisible'             => $this->is_visible,
            'isGroup'               => $this->is_group,
            'description'           => $this->description,
            'feature'               => AppModuleFeatureResource::make($this->whenLoaded('feature')),
            'parent'                => $this->when($this->relationLoaded('parent'), fn() => [
                'id'       => $this->parent?->id,
                'menuName' => $this->parent?->menu_name,
            ]),
            'children'              => self::collection($this->whenLoaded('children')),
        ];
    }
}
