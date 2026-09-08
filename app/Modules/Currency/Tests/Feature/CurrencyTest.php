<?php

namespace Modules\Currency\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Currency\Models\Currency;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class CurrencyTest extends TestCase
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
        $this->getJson('/api/currencies')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_CURRENCY', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-currencies@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no CURRENCY_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/currencies')
            ->assertStatus(403);
    }

    public function test_can_list_currencies(): void
    {
        Currency::create(['name' => 'Test Dollar', 'code' => 'TSD', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active', 'symbol_position' => 'before', 'exchange_rate' => 1.0]);

        $this->withToken($this->token)->getJson('/api/currencies')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_currencies(): void
    {
        $data = ['name' => 'Test Dollar', 'code' => 'TSD', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active', 'symbol_position' => 'before', 'exchange_rate' => 1.0];

        $this->withToken($this->token)->postJson('/api/currencies', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('currencies', $data);
    }

    public function test_can_show_currencies(): void
    {
        $currency = Currency::create(['name' => 'Test Dollar', 'code' => 'TSD', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active', 'symbol_position' => 'before', 'exchange_rate' => 1.0]);

        $this->withToken($this->token)->getJson('/api/currencies/'.$currency->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_currencies(): void
    {
        $currency = Currency::create(['name' => 'Test Dollar', 'code' => 'TSD', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active', 'symbol_position' => 'before', 'exchange_rate' => 1.0]);
        $data = ['name' => 'Updated Dollar'];

        $this->withToken($this->token)->putJson('/api/currencies/'.$currency->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('currencies', $data);
    }

    public function test_can_delete_currencies(): void
    {
        $currency = Currency::create(['name' => 'Test Dollar', 'code' => 'TSD', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active', 'symbol_position' => 'before', 'exchange_rate' => 1.0]);

        $this->withToken($this->token)->deleteJson('/api/currencies/'.$currency->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/currencies', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
