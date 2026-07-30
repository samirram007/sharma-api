<?php

namespace Modules\VoucherEntry\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VoucherEntry\Models\VoucherEntry;
use Tests\TestCase;

class VoucherEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_voucher_entries(): void
    {
        $response = $this->getJson('/api/voucher_entries');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_voucher_entry(): void
    {
        $data = ['name' => 'Test VoucherEntry'];

        $response = $this->postJson('/api/voucher_entries', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_entries', $data);
    }

    public function test_can_show_voucher_entry(): void
    {
        $VoucherEntry = VoucherEntry::factory()->create();

        $response = $this->getJson('/api/voucher_entries/'.$VoucherEntry->id);
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

    public function test_can_update_voucher_entry(): void
    {
        $VoucherEntry = VoucherEntry::factory()->create();
        $data = ['name' => 'Updated VoucherEntry'];

        $response = $this->putJson('/api/voucher_entries/'.$VoucherEntry->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('voucher_entries', $data);
    }

    public function test_can_delete_voucher_entry(): void
    {
        $VoucherEntry = VoucherEntry::factory()->create();

        $response = $this->deleteJson('/api/voucher_entries/'.$VoucherEntry->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('voucher_entries', ['id' => $VoucherEntry->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/voucher_entries', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
