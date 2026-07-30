<?php

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_users(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_user(): void
    {
        $data = ['name' => 'Test User'];

        $response = $this->postJson('/api/users', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('users', $data);
    }

    public function test_can_show_user(): void
    {
        $User = User::factory()->create();

        $response = $this->getJson('/api/users/'.$User->id);
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

    public function test_can_update_user(): void
    {
        $User = User::factory()->create();
        $data = ['name' => 'Updated User'];

        $response = $this->putJson('/api/users/'.$User->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('users', $data);
    }

    public function test_can_delete_user(): void
    {
        $User = User::factory()->create();

        $response = $this->deleteJson('/api/users/'.$User->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $User->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/users', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
