<?php

namespace Modules\Company\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Company\Models\Company;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_companies(): void
    {
        $response = $this->getJson('/api/companies');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_company(): void
    {
        $data = ['name' => 'Test Company'];

        $response = $this->postJson('/api/companies', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('companies', $data);
    }

    public function test_can_show_company(): void
    {
        $Company = Company::factory()->create();

        $response = $this->getJson('/api/companies/'.$Company->id);
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

    public function test_can_update_company(): void
    {
        $Company = Company::factory()->create();
        $data = ['name' => 'Updated Company'];

        $response = $this->putJson('/api/companies/'.$Company->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('companies', $data);
    }

    public function test_can_delete_company(): void
    {
        $Company = Company::factory()->create();

        $response = $this->deleteJson('/api/companies/'.$Company->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('companies', ['id' => $Company->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/companies', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
