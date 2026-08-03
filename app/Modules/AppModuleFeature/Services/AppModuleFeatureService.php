<?php

namespace Modules\AppModuleFeature\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AppModuleFeature\Contracts\AppModuleFeatureServiceInterface;
use Modules\AppModuleFeature\Models\AppModuleFeature;

class AppModuleFeatureService extends BaseService implements AppModuleFeatureServiceInterface
{
    protected string $modelClass = AppModuleFeature::class;

    protected array $defaultResource = ['module'];

    public function getByRoleAndModule(int $role_id, int $module_id): Collection
    {

        $data = AppModuleFeature::where('app_module_id', $module_id)
            ->with([
                'module',
                'role_permissions' => function ($query) use ($role_id) {
                    $query->where('role_id', $role_id);
                },
            ])
            ->get();

        // dd($data->toArray());
        return $data;
        // return AppModuleFeature::where('app_module_id', $module_id)->get();
    }

    public function getAllWithRolePermissions(int $role_id): Collection
    {
        return AppModuleFeature::with([
            'module',
            'role_permissions' => function ($query) use ($role_id) {
                $query->where('role_id', $role_id);
            },
        ])
            ->orderBy('app_module_id')
            ->orderBy('id')
            ->get();
    }
}
