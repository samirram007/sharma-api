<?php

namespace Modules\UserRole\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\UserRole\Models\UserRole;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_user_roles(): void
    {
        $response = $this->getJson('/api/user_roles');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_user_role(): void
    {
        $data = ['name' => 'Test UserRole'];

        $response = $this->postJson('/api/user_roles', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('user_roles', $data);
    }

    public function test_can_show_user_role(): void
    {
        $UserRole = UserRole::factory()->create();

        $response = $this->getJson('/api/user_roles/'.$UserRole->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_update_user_role(): void
    {
        $UserRole = UserRole::factory()->create();
        $data = ['name' => 'Updated UserRole'];

        $response = $this->putJson('/api/user_roles/'.$UserRole->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('user_roles', $data);
    }

    public function test_can_delete_user_role(): void
    {
        $UserRole = UserRole::factory()->create();

        $response = $this->deleteJson('/api/user_roles/'.$UserRole->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('user_roles', ['id' => $UserRole->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/user_roles', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
