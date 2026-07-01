<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Controllers\Api\MenuController;

/*
|--------------------------------------------------------------------------
| Menu Module — All menu-related API routes consolidated here
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.cookies'])->group(function () {

    // ── Menu CRUD ────────────────────────────────────────────────
    Route::apiResource('menus', MenuController::class);

    // ── Menu Tree (management UI — all entries) ─────────────────
    Route::get('/menu_tree', [MenuController::class, 'tree']);

    // ── Drag-and-drop reorder ───────────────────────────────────
    Route::post('/menus/reorder', [MenuController::class, 'reorder']);

    // ── User-facing endpoints (moved from Auth module) ──────────
    Route::get('/user/menu', [MenuController::class, 'userMenu']);
    Route::get('/auth/menus', [MenuController::class, 'userMenuTree']);

    // ── Role permissions (moved from AppModuleFeature module) ───
    Route::get('/role/{role_id}/menu-permissions', [MenuController::class, 'roleMenuPermissions']);
});

