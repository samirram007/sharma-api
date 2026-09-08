<?php

namespace Modules\Country\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Country\Models\Country;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class CountryTest extends TestCase
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
        $this->getJson('/api/countries')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_COUNTRY', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-countries@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no COUNTRY_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/countries')
            ->assertStatus(403);
    }

    public function test_can_list_countries(): void
    {
        Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN']);

        $this->withToken($this->token)->getJson('/api/countries')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_countries(): void
    {
        $data = ['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN'];

        $this->withToken($this->token)->postJson('/api/countries', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('countries', $data);
    }

    public function test_can_show_countries(): void
    {
        $country = Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN']);

        $this->withToken($this->token)->getJson('/api/countries/'.$country->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_countries(): void
    {
        $country = Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN']);
        $data = ['phone_code' => '+44', 'iso_code' => 'GB'];

        $this->withToken($this->token)->putJson('/api/countries/'.$country->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('countries', $data);
    }

    public function test_can_delete_countries(): void
    {
        $country = Country::create(['name' => 'Test Land', 'phone_code' => '+91', 'iso_code' => 'IN']);

        $this->withToken($this->token)->deleteJson('/api/countries/'.$country->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/countries', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone_code', 'iso_code']);
    }
}
