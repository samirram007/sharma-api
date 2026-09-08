<?php

namespace Modules\Journal\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Journal\Models\Journal;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\ActingAsSuperAdmin;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use ActingAsSuperAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actAsSuperAdmin();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/journals')
            ->assertStatus(401);
    }

    public function test_index_denied_without_feature_permission(): void
    {
        $role = Role::create([
            'name' => 'Gate Viewer', 'code' => 'GATE_VIEWER_JOURNAL', 'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Gate Viewer User',
            'email' => 'gate-viewer-journals@example.com',
            'password' => 'password',
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Authenticated but the role holds no JOURNAL_MENU_VIEW grant.
        $this->withToken($token)->getJson('/api/journals')
            ->assertStatus(403);
    }

    public function test_can_list_journals(): void
    {
        Journal::create(['voucher_id' => 1, 'entry_index' => 1, 'account_ledger_id' => 1, 'debit_amount' => 100.0, 'credit_amount' => 0.0]);

        $this->withToken($this->token)->getJson('/api/journals')
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_create_journals(): void
    {
        $data = ['voucher_id' => 1, 'entry_index' => 1, 'account_ledger_id' => 1, 'debit_amount' => 100.0, 'credit_amount' => 0.0];

        $this->withToken($this->token)->postJson('/api/journals', $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('journals', $data);
    }

    public function test_can_show_journals(): void
    {
        $journal = Journal::create(['voucher_id' => 1, 'entry_index' => 1, 'account_ledger_id' => 1, 'debit_amount' => 100.0, 'credit_amount' => 0.0]);

        $this->withToken($this->token)->getJson('/api/journals/'.$journal->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_can_update_journals(): void
    {
        $journal = Journal::create(['voucher_id' => 1, 'entry_index' => 1, 'account_ledger_id' => 1, 'debit_amount' => 100.0, 'credit_amount' => 0.0]);
        $data = ['debit_amount' => 150.0];

        $this->withToken($this->token)->putJson('/api/journals/'.$journal->id, $data)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('journals', $data);
    }

    public function test_can_delete_journals(): void
    {
        $journal = Journal::create(['voucher_id' => 1, 'entry_index' => 1, 'account_ledger_id' => 1, 'debit_amount' => 100.0, 'credit_amount' => 0.0]);

        $this->withToken($this->token)->deleteJson('/api/journals/'.$journal->id)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        $this->withToken($this->token)->postJson('/api/journals', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['voucher_id', 'entry_index', 'account_ledger_id']);
    }
}
