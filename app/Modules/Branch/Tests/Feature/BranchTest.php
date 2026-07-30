<?php

namespace Modules\Branch\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Branch\Models\Branch;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_branches(): void
    {
        $response = $this->getJson('/api/branches');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_branch(): void
    {
        $data = ['name' => 'Test Branch'];

        $response = $this->postJson('/api/branches', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('branches', $data);
    }

    public function test_can_show_branch(): void
    {
        $Branch = Branch::factory()->create();

        $response = $this->getJson('/api/branches/'.$Branch->id);
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

    public function test_can_update_branch(): void
    {
        $Branch = Branch::factory()->create();
        $data = ['name' => 'Updated Branch'];

        $response = $this->putJson('/api/branches/'.$Branch->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('branches', $data);
    }

    public function test_can_delete_branch(): void
    {
        $Branch = Branch::factory()->create();

        $response = $this->deleteJson('/api/branches/'.$Branch->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('branches', ['id' => $Branch->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/branches', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
