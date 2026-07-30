<?php

namespace App\Support;

class ModuleConnectionResolver
{
    public static function resolve(?string $class = null): string
    {
        $default = config('module-database.default');

        if (! $class) {
            return $default;
        }

        // Extract module from namespace: Modules\{Module}\...
        if (preg_match('/Modules\\\\([^\\\\]+)/', $class, $m)) {
            $module = $m[1];

            return config("module-database.map.$module", $default);
        }

        return $default;
    }
}
