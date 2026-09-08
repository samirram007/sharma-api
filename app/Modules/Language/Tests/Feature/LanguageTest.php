<?php

namespace Modules\Language\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Language\Models\Language;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class LanguageTest extends TestCase
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
        $this->getJson('/api/languages')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_LANGUAGE', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-languages@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no LANGUAGE_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/languages')
            ->assertStatus(403);
    }

    public function test_can_list_languages(): void
    {
        Language::create(['name' => 'Test Language', 'code' => 'tl', 'locale' => 'tl_IN']);

        $this->withToken($this->token)->getJson('/api/languages')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_languages(): void
    {
        $data = ['name' => 'Test Language', 'code' => 'tl', 'locale' => 'tl_IN'];

        $this->withToken($this->token)->postJson('/api/languages', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('languages', $data);
    }

    public function test_can_show_languages(): void
    {
        $language = Language::create(['name' => 'Test Language', 'code' => 'tl', 'locale' => 'tl_IN']);

        $this->withToken($this->token)->getJson('/api/languages/'.$language->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_languages(): void
    {
        $language = Language::create(['name' => 'Test Language', 'code' => 'tl', 'locale' => 'tl_IN']);
        $data = ['flag' => 'updated-flag.png'];

        $this->withToken($this->token)->putJson('/api/languages/'.$language->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('languages', $data);
    }

    public function test_can_delete_languages(): void
    {
        $language = Language::create(['name' => 'Test Language', 'code' => 'tl', 'locale' => 'tl_IN']);

        $this->withToken($this->token)->deleteJson('/api/languages/'.$language->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/languages', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
