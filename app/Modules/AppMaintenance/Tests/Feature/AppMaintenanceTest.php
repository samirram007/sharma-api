<?php

namespace Modules\AppMaintenance\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AppMaintenance\Models\AppMaintenance;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class AppMaintenanceTest extends TestCase
{
    use ActingAsSuperAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actAsSuperAdmin();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/app_maintenances')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_APPMAINTENANCE', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-app_maintenances@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no APP_MAINTENANCE_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/app_maintenances')
            ->assertStatus(403);
    }

    public function test_can_list_app_maintenances(): void
    {
        AppMaintenance::create(['name' => 'Test Maintenance']);

        $this->withToken($this->token)->getJson('/api/app_maintenances')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_app_maintenances(): void
    {
        $data = ['name' => 'Test Maintenance'];

        $this->withToken($this->token)->postJson('/api/app_maintenances', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('app_maintenances', $data);
    }

    public function test_can_show_app_maintenances(): void
    {
        $maintenance = AppMaintenance::create(['name' => 'Test Maintenance']);

        $this->withToken($this->token)->getJson('/api/app_maintenances/'.$maintenance->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_app_maintenances(): void
    {
        $maintenance = AppMaintenance::create(['name' => 'Test Maintenance']);
        $data = ['name' => 'Updated Maintenance'];

        $this->withToken($this->token)->putJson('/api/app_maintenances/'.$maintenance->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('app_maintenances', $data);
    }

    public function test_can_delete_app_maintenances(): void
    {
        $maintenance = AppMaintenance::create(['name' => 'Test Maintenance']);

        $this->withToken($this->token)->deleteJson('/api/app_maintenances/'.$maintenance->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('app_maintenances', ['id' => $maintenance->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/app_maintenances', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
