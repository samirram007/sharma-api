<?php

namespace Modules\Setting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Role\Models\Role;
use Modules\Setting\Models\Setting;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class SettingTest extends TestCase
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
        $this->getJson('/api/settings')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_SETTING', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-settings@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no SETTINGS_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/settings')
            ->assertStatus(403);
    }

    public function test_can_list_settings(): void
    {
        Setting::create(['property' => 'test_setting', 'value' => 'test_value']);

        $this->withToken($this->token)->getJson('/api/settings')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_settings(): void
    {
        $data = ['property' => 'test_setting', 'value' => 'test_value'];

        $this->withToken($this->token)->postJson('/api/settings', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('settings', $data);
    }

    public function test_can_show_settings(): void
    {
        $setting = Setting::create(['property' => 'test_setting', 'value' => 'test_value']);

        $this->withToken($this->token)->getJson('/api/settings/'.$setting->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_settings(): void
    {
        $setting = Setting::create(['property' => 'test_setting', 'value' => 'test_value']);
        $data = ['value' => 'updated_value'];

        $this->withToken($this->token)->putJson('/api/settings/'.$setting->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('settings', $data);
    }

    public function test_can_delete_settings(): void
    {
        $setting = Setting::create(['property' => 'test_setting', 'value' => 'test_value']);

        $this->withToken($this->token)->deleteJson('/api/settings/'.$setting->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/settings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property']);
    }
}
