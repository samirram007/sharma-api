<?php

namespace Modules\Distributor\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Distributor\Models\Distributor;
use Tests\TestCase;

class DistributorTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_distributors(): void
    {
        $response = $this->getJson('/api/distributors');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_distributor(): void
    {
        $data = ['name' => 'Test Distributor'];

        $response = $this->postJson('/api/distributors', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('distributors', $data);
    }

    public function test_can_show_distributor(): void
    {
        $Distributor = Distributor::factory()->create();

        $response = $this->getJson('/api/distributors/'.$Distributor->id);
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

    public function test_can_update_distributor(): void
    {
        $Distributor = Distributor::factory()->create();
        $data = ['name' => 'Updated Distributor'];

        $response = $this->putJson('/api/distributors/'.$Distributor->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('distributors', $data);
    }

    public function test_can_delete_distributor(): void
    {
        $Distributor = Distributor::factory()->create();

        $response = $this->deleteJson('/api/distributors/'.$Distributor->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('distributors', ['id' => $Distributor->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/distributors', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
