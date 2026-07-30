<?php

namespace Modules\AccountNature\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccountNature\Models\AccountNature;
use Tests\TestCase;

class AccountNatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_account_natures(): void
    {
        $response = $this->getJson('/api/account_natures');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_account_nature(): void
    {
        $data = ['name' => 'Test AccountNature'];

        $response = $this->postJson('/api/account_natures', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('account_natures', $data);
    }

    public function test_can_show_account_nature(): void
    {
        $AccountNature = AccountNature::factory()->create();

        $response = $this->getJson('/api/account_natures/'.$AccountNature->id);
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

    public function test_can_update_account_nature(): void
    {
        $AccountNature = AccountNature::factory()->create();
        $data = ['name' => 'Updated AccountNature'];

        $response = $this->putJson('/api/account_natures/'.$AccountNature->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('account_natures', $data);
    }

    public function test_can_delete_account_nature(): void
    {
        $AccountNature = AccountNature::factory()->create();

        $response = $this->deleteJson('/api/account_natures/'.$AccountNature->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('account_natures', ['id' => $AccountNature->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/account_natures', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
