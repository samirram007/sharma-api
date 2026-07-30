<?php

namespace Modules\AppMaintenance\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppMaintenance\Models\AppMaintenance;
use Tests\TestCase;

class AppMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_app_maintenances(): void
    {
        $response = $this->getJson('/api/app_maintenances');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_app_maintenance(): void
    {
        $data = ['name' => 'Test AppMaintenance'];

        $response = $this->postJson('/api/app_maintenances', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('app_maintenances', $data);
    }

    public function test_can_show_app_maintenance(): void
    {
        $AppMaintenance = AppMaintenance::factory()->create();

        $response = $this->getJson('/api/app_maintenances/'.$AppMaintenance->id);
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

    public function test_can_update_app_maintenance(): void
    {
        $AppMaintenance = AppMaintenance::factory()->create();
        $data = ['name' => 'Updated AppMaintenance'];

        $response = $this->putJson('/api/app_maintenances/'.$AppMaintenance->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('app_maintenances', $data);
    }

    public function test_can_delete_app_maintenance(): void
    {
        $AppMaintenance = AppMaintenance::factory()->create();

        $response = $this->deleteJson('/api/app_maintenances/'.$AppMaintenance->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('app_maintenances', ['id' => $AppMaintenance->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/app_maintenances', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
