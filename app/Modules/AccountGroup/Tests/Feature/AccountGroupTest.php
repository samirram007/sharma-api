<?php

namespace Modules\AccountGroup\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccountGroup\Models\AccountGroup;
use Tests\TestCase;

class AccountGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_account_groups(): void
    {
        $response = $this->getJson('/api/account_groups');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_account_group(): void
    {
        $data = ['name' => 'Test AccountGroup'];

        $response = $this->postJson('/api/account_groups', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('account_groups', $data);
    }

    public function test_can_show_account_group(): void
    {
        $AccountGroup = AccountGroup::factory()->create();

        $response = $this->getJson('/api/account_groups/'.$AccountGroup->id);
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

    public function test_can_update_account_group(): void
    {
        $AccountGroup = AccountGroup::factory()->create();
        $data = ['name' => 'Updated AccountGroup'];

        $response = $this->putJson('/api/account_groups/'.$AccountGroup->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('account_groups', $data);
    }

    public function test_can_delete_account_group(): void
    {
        $AccountGroup = AccountGroup::factory()->create();

        $response = $this->deleteJson('/api/account_groups/'.$AccountGroup->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('account_groups', ['id' => $AccountGroup->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/account_groups', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
