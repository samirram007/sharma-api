<?php

namespace Modules\Post\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Post\Models\Post;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class PostTest extends TestCase
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
        $this->getJson('/api/posts')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_POST', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-posts@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no POST_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/posts')
            ->assertStatus(403);
    }

    public function test_can_list_posts(): void
    {
        Post::create(['name' => 'Test Post']);

        $this->withToken($this->token)->getJson('/api/posts')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_posts(): void
    {
        $data = ['name' => 'Test Post'];

        $this->withToken($this->token)->postJson('/api/posts', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', $data);
    }

    public function test_can_show_posts(): void
    {
        $post = Post::create(['name' => 'Test Post']);

        $this->withToken($this->token)->getJson('/api/posts/'.$post->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_posts(): void
    {
        $post = Post::create(['name' => 'Test Post']);
        $data = ['name' => 'Updated Post'];

        $this->withToken($this->token)->putJson('/api/posts/'.$post->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', $data);
    }

    public function test_can_delete_posts(): void
    {
        $post = Post::create(['name' => 'Test Post']);

        $this->withToken($this->token)->deleteJson('/api/posts/'.$post->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
