<?php

namespace Modules\VoucherClassification\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VoucherClassification\Models\VoucherClassification;
use Tests\TestCase;

class VoucherClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_voucher_classifications(): void
    {
        $response = $this->getJson('/api/voucher_classifications');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_voucher_classification(): void
    {
        $data = ['name' => 'Test VoucherClassification'];

        $response = $this->postJson('/api/voucher_classifications', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_classifications', $data);
    }

    public function test_can_show_voucher_classification(): void
    {
        $VoucherClassification = VoucherClassification::factory()->create();

        $response = $this->getJson('/api/voucher_classifications/'.$VoucherClassification->id);
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

    public function test_can_update_voucher_classification(): void
    {
        $VoucherClassification = VoucherClassification::factory()->create();
        $data = ['name' => 'Updated VoucherClassification'];

        $response = $this->putJson('/api/voucher_classifications/'.$VoucherClassification->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_classifications', $data);
    }

    public function test_can_delete_voucher_classification(): void
    {
        $VoucherClassification = VoucherClassification::factory()->create();

        $response = $this->deleteJson('/api/voucher_classifications/'.$VoucherClassification->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('voucher_classifications', ['id' => $VoucherClassification->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/voucher_classifications', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
