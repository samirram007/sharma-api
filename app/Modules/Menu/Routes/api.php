<?php

use Illuminate\Support\Facades\Route;
use Modules\Menu\Controllers\Api\MenuController;

/*
|--------------------------------------------------------------------------
| Menu Module — All menu-related API routes consolidated here
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.cookies'])->group(function () {

    // ── Search (must be before apiResource to avoid {menu} catch-all) ─
    Route::get('/menus/search', [MenuController::class, 'search']);

    // ── Menu CRUD ────────────────────────────────────────────────
    Route::apiResource('menus', MenuController::class);

    // ── Menu Tree (management UI — all entries) ─────────────────
    Route::get('/menu_tree', [MenuController::class, 'tree']);

    // ── Drag-and-drop reorder ───────────────────────────────────
    Route::post('/menus/reorder', [MenuController::class, 'reorder']);

    // ── Batch operations ────────────────────────────────────────
    Route::post('/menus/batch-update', [MenuController::class, 'batchUpdate']);
    Route::post('/menus/batch-delete', [MenuController::class, 'batchDelete']);

    // ── Duplicate ───────────────────────────────────────────────
    Route::post('/menus/{id}/duplicate', [MenuController::class, 'duplicate']);

    // ── Import / Export ──────────────────────────────────────────
    Route::get('/menus/export', [MenuController::class, 'export']);
    Route::post('/menus/import', [MenuController::class, 'import']);

    // ── User-facing endpoints (moved from Auth module) ──────────
    Route::get('/user/menu', [MenuController::class, 'userMenu']);
    Route::get('/auth/menus', [MenuController::class, 'userMenuTree']);
    Route::get('/auth/top_menus', [MenuController::class, 'userTopMenuTree']);

    // ── Role permissions (moved from AppModuleFeature module) ───
    Route::get('/role/{role_id}/menu-permissions', [MenuController::class, 'roleMenuPermissions']);
});
