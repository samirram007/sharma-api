<?php

namespace Modules\State\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Country\Models\Country;
use Modules\Role\Models\Role;
use Modules\State\Models\State;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class StateTest extends TestCase
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
        $this->getJson('/api/states')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_STATE', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-states@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no STATE_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/states')
            ->assertStatus(403);
    }

    public function test_can_list_states(): void
    {
        State::create(['name' => 'Test State', 'code' => 'TS', 'country_id' => Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN'])->id, 'gst_code' => 'TS-GST']);

        $this->withToken($this->token)->getJson('/api/states')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_states(): void
    {
        $data = ['name' => 'Test State', 'code' => 'TS', 'country_id' => (string) Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN'])->id, 'gst_code' => 'TS-GST'];

        $this->withToken($this->token)->postJson('/api/states', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('states', $data);
    }

    public function test_can_show_states(): void
    {
        $state = State::create(['name' => 'Test State', 'code' => 'TS', 'country_id' => Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN'])->id, 'gst_code' => 'TS-GST']);

        $this->withToken($this->token)->getJson('/api/states/'.$state->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_states(): void
    {
        $state = State::create(['name' => 'Test State', 'code' => 'TS', 'country_id' => Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN'])->id, 'gst_code' => 'TS-GST']);
        $data = ['gst_code' => 'TS-UPD', 'code' => 'TSU', 'country_id' => (string) $state->country_id];

        $this->withToken($this->token)->putJson('/api/states/'.$state->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('states', $data);
    }

    public function test_can_delete_states(): void
    {
        $state = State::create(['name' => 'Test State', 'code' => 'TS', 'country_id' => Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN'])->id, 'gst_code' => 'TS-GST']);

        $this->withToken($this->token)->deleteJson('/api/states/'.$state->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('states', ['id' => $state->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/states', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'country_id']);
    }
}
