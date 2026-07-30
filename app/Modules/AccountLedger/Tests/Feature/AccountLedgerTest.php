<?php

namespace Modules\AccountLedger\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccountLedger\Models\AccountLedger;
use Tests\TestCase;

class AccountLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_account_ledgers(): void
    {
        $response = $this->getJson('/api/account_ledgers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);
    }

    public function test_can_create_account_ledger(): void
    {
        $data = ['name' => 'Test AccountLedger'];

        $response = $this->postJson('/api/account_ledgers', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('account_ledgers', $data);
    }

    public function test_can_show_account_ledger(): void
    {
        $AccountLedger = AccountLedger::factory()->create();

        $response = $this->getJson('/api/account_ledgers/'.$AccountLedger->id);
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

    public function test_can_update_account_ledger(): void
    {
        $AccountLedger = AccountLedger::factory()->create();
        $data = ['name' => 'Updated AccountLedger'];

        $response = $this->putJson('/api/account_ledgers/'.$AccountLedger->id, $data);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseHas('account_ledgers', $data);
    }

    public function test_can_delete_account_ledger(): void
    {
        $AccountLedger = AccountLedger::factory()->create();

        $response = $this->deleteJson('/api/account_ledgers/'.$AccountLedger->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
            ]);

        $this->assertDatabaseMissing('account_ledgers', ['id' => $AccountLedger->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/account_ledgers', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
