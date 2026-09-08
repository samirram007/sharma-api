<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Server-side permission gate.
 *
 * Usage on a route or group:
 *   ->middleware('feature.permission:CURRENCY_MENU_VIEW')
 *   ->middleware('feature.permission:'.RequireFeaturePermission::SUPERADMIN.',COUNTRY_MENU_VIEW')
 *
 * Grants access when the authenticated user's role(s) hold an allowed
 * `role_permissions` row for ANY of the listed feature codes. Roles whose
 * `code` is in SUPERADMIN_ROLE_CODES (Super Admin / Developer by seed data)
 * bypass the check, so the API stays operable while permissions are being
 * configured through the Roles & Permissions UI.
 *
 * This closes (part of) the RBAC gap: previously ALL module routes were
 * authenticated-only — any signed-in user could read/write any resource.
 */
class RequireFeaturePermission
{
    /**
     * Role codes that bypass feature checks (full access).
     * Kept in one place so tests/seeders can reference the same contract.
     */
    public const SUPERADMIN_ROLE_CODES = ['SUPER_ADMIN', 'DEVELOPER'];

    public function handle(Request $request, Closure $next, string ...$featureCodes): Response
    {
        $user = $request->user();

        if (! $user) {
            // No authenticated user (jwt.cookies should have run first).
            abort(401, 'Unauthenticated.');
        }

        // Super admin / developer roles bypass feature checks.
        $roleCodes = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->pluck('roles.code');

        if ($roleCodes->intersect(self::SUPERADMIN_ROLE_CODES)->isNotEmpty()) {
            return $next($request);
        }

        if (empty($featureCodes)) {
            abort(500, 'feature.permission middleware requires at least one feature code.');
        }

        $allowed = DB::table('role_permissions')
            ->join('app_module_features', 'app_module_features.id', '=', 'role_permissions.app_module_feature_id')
            ->whereIn('role_id', function ($query) use ($user) {
                $query->select('role_id')->from('user_roles')->where('user_id', $user->id);
            })
            ->where('role_permissions.is_allowed', true)
            ->whereIn('app_module_features.code', $featureCodes)
            ->exists();

        if (! $allowed) {
            throw new AccessDeniedHttpException(
                'You do not have permission to perform this action.'
            );
        }

        return $next($request);
    }
}
