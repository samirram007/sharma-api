<?php

namespace Modules\Menu\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // PATCH is used for quick single-field toggles (visibility, top menu,
        // status) that send only the changed attribute — those fields must not
        // be required then. Full create (POST) and update (PUT) still require
        // the core fields.
        $partial = $this->isMethod('PATCH');

        $rules = [
            'app_module_feature_id' => [$partial ? 'sometimes' : 'required', 'numeric', 'exists:app_module_features,id'],
            'menu_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'numeric', 'exists:menu,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_group' => ['sometimes', 'boolean'],
            'is_top_menu' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'app_module_feature_id.required' => 'The feature field is required.',
            'app_module_feature_id.exists' => 'The selected feature is invalid.',
            'menu_name.required' => 'The menu name is required.',
            'parent_id.exists' => 'The selected parent menu is invalid.',
        ];
    }
}
