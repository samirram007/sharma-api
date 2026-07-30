<?php

namespace Modules\VoucherReference\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VoucherReference\Models\VoucherReference;
use Tests\TestCase;

class VoucherReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_voucher_references(): void
    {
        $response = $this->getJson('/api/voucher_references');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_voucher_reference(): void
    {
        $data = ['name' => 'Test VoucherReference'];

        $response = $this->postJson('/api/voucher_references', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_references', $data);
    }

    public function test_can_show_voucher_reference(): void
    {
        $VoucherReference = VoucherReference::factory()->create();

        $response = $this->getJson('/api/voucher_references/'.$VoucherReference->id);
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

    public function test_can_update_voucher_reference(): void
    {
        $VoucherReference = VoucherReference::factory()->create();
        $data = ['name' => 'Updated VoucherReference'];

        $response = $this->putJson('/api/voucher_references/'.$VoucherReference->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_references', $data);
    }

    public function test_can_delete_voucher_reference(): void
    {
        $VoucherReference = VoucherReference::factory()->create();

        $response = $this->deleteJson('/api/voucher_references/'.$VoucherReference->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('voucher_references', ['id' => $VoucherReference->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/voucher_references', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
