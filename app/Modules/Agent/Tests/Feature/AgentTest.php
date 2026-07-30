<?php

namespace Modules\Agent\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Agent\Models\Agent;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_agents(): void
    {
        $response = $this->getJson('/api/agents');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_agent(): void
    {
        $data = ['name' => 'Test Agent'];

        $response = $this->postJson('/api/agents', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('agents', $data);
    }

    public function test_can_show_agent(): void
    {
        $Agent = Agent::factory()->create();

        $response = $this->getJson('/api/agents/'.$Agent->id);
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

    public function test_can_update_agent(): void
    {
        $Agent = Agent::factory()->create();
        $data = ['name' => 'Updated Agent'];

        $response = $this->putJson('/api/agents/'.$Agent->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('agents', $data);
    }

    public function test_can_delete_agent(): void
    {
        $Agent = Agent::factory()->create();

        $response = $this->deleteJson('/api/agents/'.$Agent->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('agents', ['id' => $Agent->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/agents', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
