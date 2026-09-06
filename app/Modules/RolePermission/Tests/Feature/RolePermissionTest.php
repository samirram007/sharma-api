<?php

use Modules\AppModule\Models\AppModule;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\Role\Models\Role;
use Modules\RolePermission\Models\RolePermission;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * HTTP tests for the role-permission CRUD API.
 *
 * The routes live at /api/permissions (NOT /api/role_permissions) and are
 * protected by the jwt.cookies middleware, so every request is authenticated
 * with a real JWT minted for a freshly created user. The unified response
 * envelope is { success, code, message, data } with camelCase payload keys
 * (roleId / appModuleFeatureId / isAllowed).
 */
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Permission Test User',
        'email' => 'permission-test@example.com',
        'password' => 'password',
    ]);

    // Mint a JWT so the jwt.cookies middleware authenticates the request.
    $this->token = JWTAuth::fromUser($this->user);

    $this->role = Role::create([
        'name' => 'Permission Test Role',
        'code' => 'PERM_TEST_ROLE',
        'status' => 'active',
    ]);

    $module = AppModule::create([
        'name' => 'Permission Test Module',
        'code' => 'PERM_TEST_MODULE',
    ]);

    $this->feature = AppModuleFeature::create([
        'app_module_id' => $module->id,
        'name' => 'Permission Test Feature',
        'code' => 'PERM_TEST_FEATURE',
    ]);
});

// ---------------------------------------------------------------------------
//  index() — GET /api/permissions
// ---------------------------------------------------------------------------

test('index() lists role permissions in the unified envelope', function () {
    RolePermission::create([
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => true,
    ]);

    $this->withToken($this->token)
        ->getJson('/api/permissions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => [
                '*' => ['id', 'roleId', 'appModuleFeatureId', 'isAllowed'],
            ],
        ]);
});

// ---------------------------------------------------------------------------
//  store() — POST /api/permissions
// ---------------------------------------------------------------------------

test('store() creates a role permission', function () {
    $this->withToken($this->token)
        ->postJson('/api/permissions', [
            'roleId' => $this->role->id,
            'appModuleFeatureId' => $this->feature->id,
            'isAllowed' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.roleId', $this->role->id)
        ->assertJsonPath('data.appModuleFeatureId', $this->feature->id)
        ->assertJsonPath('data.isAllowed', true);

    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => 1,
    ]);
});

test('store() accepts false isAllowed (deny)', function () {
    $this->withToken($this->token)
        ->postJson('/api/permissions', [
            'roleId' => $this->role->id,
            'appModuleFeatureId' => $this->feature->id,
            'isAllowed' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('data.isAllowed', false);

    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => 0,
    ]);
});

test('store() returns 422 for missing required fields', function () {
    $this->withToken($this->token)
        ->postJson('/api/permissions', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role_id', 'app_module_feature_id', 'is_allowed']);
});

test('store() returns 422 for an unknown role', function () {
    $this->withToken($this->token)
        ->postJson('/api/permissions', [
            'roleId' => 999999,
            'appModuleFeatureId' => $this->feature->id,
            'isAllowed' => true,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['role_id']);
});

// ---------------------------------------------------------------------------
//  show() — GET /api/permissions/{id}
// ---------------------------------------------------------------------------

test('show() returns a single role permission', function () {
    $permission = RolePermission::create([
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => true,
    ]);

    $this->withToken($this->token)
        ->getJson('/api/permissions/'.$permission->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'code',
            'message',
            'data' => ['id', 'roleId', 'appModuleFeatureId', 'isAllowed'],
        ]);
});

test('show() returns 404 for a missing permission', function () {
    $this->withToken($this->token)
        ->getJson('/api/permissions/999999')
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
//  update() — PUT /api/permissions/{id}
// ---------------------------------------------------------------------------

test('update() toggles is_allowed and persists', function () {
    $permission = RolePermission::create([
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => true,
    ]);

    $this->withToken($this->token)
        ->putJson('/api/permissions/'.$permission->id, [
            'roleId' => $this->role->id,
            'appModuleFeatureId' => $this->feature->id,
            'isAllowed' => false,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.isAllowed', false);

    $this->assertDatabaseHas('role_permissions', [
        'id' => $permission->id,
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => 0,
    ]);

    // Round-trip back to true — regression guard for the cached-model update
    // bug in BaseRepository where repeated writes could silently no-op.
    $this->withToken($this->token)
        ->putJson('/api/permissions/'.$permission->id, [
            'roleId' => $this->role->id,
            'appModuleFeatureId' => $this->feature->id,
            'isAllowed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.isAllowed', true);

    $this->assertDatabaseHas('role_permissions', [
        'id' => $permission->id,
        'is_allowed' => 1,
    ]);
});

test('update() returns 404 for a missing permission', function () {
    $this->withToken($this->token)
        ->putJson('/api/permissions/999999', ['isAllowed' => false])
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
//  destroy() — DELETE /api/permissions/{id}
// ---------------------------------------------------------------------------

test('destroy() deletes a role permission', function () {
    $permission = RolePermission::create([
        'role_id' => $this->role->id,
        'app_module_feature_id' => $this->feature->id,
        'is_allowed' => true,
    ]);

    $this->withToken($this->token)
        ->deleteJson('/api/permissions/'.$permission->id)
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('role_permissions', ['id' => $permission->id]);
});

test('destroy() returns 404 for a missing permission', function () {
    $this->withToken($this->token)
        ->deleteJson('/api/permissions/999999')
        ->assertNotFound();
});
