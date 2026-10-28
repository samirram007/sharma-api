<?php

namespace Modules\Freight\Tests\Feature;

use App\Enums\MovementType;
use App\Support\DeliveryLockFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;
use Modules\VoucherParty\Models\VoucherParty;
use Modules\VoucherReference\Models\VoucherReference;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Delivery lock (postpone) coverage: lock/unlock endpoints, the reason
 * validation, the `freight_status=locked` list filter and the postponed
 * counter aggregate reported on the Freight screen header.
 *
 * The lock is an indicator only (recorded reason + who/when) — it must never
 * remove a delivery from the normal Pending/Prepared lists.
 */
class FreightDeliveryLockTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Delivery Lock Tester',
            'email' => 'delivery-lock-tester@example.com',
            'password' => 'password',
        ]);
        $this->token = JWTAuth::fromUser($this->user);

        // Postponing / releasing / viewing are permission-gated: this user
        // holds the full delivery-lock grant for the happy-path tests.
        $this->grantDeliveryLockFeatures();

        // FreightService hardcodes the delivery-note voucher type id (2001).
        DB::table('voucher_types')->insert([
            'id' => 2001,
            'name' => 'Delivery Note '.Str::random(6),
            'code' => 'DLNT-'.Str::random(4),
            'voucher_category_id' => 1,
            'status' => 'active',
        ]);
    }

    /** One delivery-note voucher with a stock journal, party & prepared fare. */
    private function createDeliveryNote(array $overrides = []): Voucher
    {
        $item = StockItem::create([
            'name' => $overrides['itemName'] ?? 'Cement OPC 53',
            'code' => $overrides['itemCode'] ?? 'CEM-'.Str::random(4),
            'status' => 'active',
        ]);

        $journal = StockJournal::create([
            'journal_no' => 'SJ-'.Str::random(6),
            'journal_date' => now(),
            'type' => 'stock',
        ]);

        $entry = StockJournalEntry::create([
            'stock_journal_id' => $journal->id,
            'entry_order' => 1,
            'stock_item_id' => $item->id,
            'actual_quantity' => 10,
            'billing_quantity' => 10,
            'movement_type' => MovementType::IN,
        ]);

        $godown = Godown::create([
            'name' => 'Lock Godown '.Str::random(4),
            'code' => 'LG-'.Str::random(3),
            'status' => 'active',
            'storage_unit_type' => 'GODOWN',
        ]);

        StockJournalGodownEntry::create([
            'stock_journal_entry_id' => $entry->id,
            'entry_order' => 1,
            'godown_id' => $godown->id,
            'actual_quantity' => 10,
            'billing_quantity' => 10,
        ]);

        $voucher = Voucher::create([
            'voucher_no' => $overrides['voucherNo'] ?? 'DLNT-'.Str::random(5),
            'voucher_date' => now(),
            'voucher_type_id' => 2001,
            'stock_journal_id' => $journal->id,
            'fiscal_year_id' => $overrides['fiscalYearId'] ?? 1,
            'company_id' => $overrides['companyId'] ?? 1,
            'status' => 'active',
        ]);

        VoucherParty::create([
            'voucher_id' => $voucher->id,
            'name' => $overrides['partyName'] ?? 'DAS CEMENT AGENCY',
            'mailing_name' => $overrides['partyName'] ?? 'DAS CEMENT AGENCY',
        ]);

        VoucherDispatchDetail::create([
            'voucher_id' => $voucher->id,
            'carrier_name' => $overrides['carrierName'] ?? 'SHARMA TRANSPORT',
            'motor_vehicle_no' => $overrides['vehicleNo'] ?? 'JH01AB1234',
            'source' => $overrides['source'] ?? 'RANCHI',
            'destination' => $overrides['destination'] ?? 'DALTONGANJ',
            'freight_charges' => $overrides['freightCharges'] ?? 500,
            'total_fare' => $overrides['totalFare'] ?? 500,
        ]);

        return $voucher;
    }

    /**
     * Attach a freight (sales, 1006) voucher to the delivery note — the
     * "freight is prepared" state the lock must remain available in.
     */
    private function createPreparedFreight(Voucher $deliveryNote): Voucher
    {
        $freight = Voucher::create([
            'voucher_no' => 'FR-'.Str::random(5),
            'voucher_date' => now(),
            'voucher_type_id' => 1006,
            'fiscal_year_id' => 1,
            'company_id' => 1,
            'module' => 'freight',
            'status' => 'active',
        ]);

        VoucherReference::create([
            'voucher_id' => $freight->id,
            'ref_voucher_id' => $deliveryNote->id,
            'type' => 'freight',
        ]);

        return $freight;
    }

    /** Give the signed-in user a fiscal-year period covering today. */
    private function attachFiscalYear(): void
    {
        DB::table('fiscal_years')->insert([
            'company_id' => 1,
            'name' => 'FY Lock Test',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_fiscal_years')->insert([
            'user_id' => $this->user->id,
            'fiscal_year_id' => 1,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'current_date' => now()->toDateString(),
        ]);
    }

    /**
     * Attach a role holding the given delivery-lock feature codes to a user
     * (defaults to the signed-in test user and the full grant).
     *
     * @param  array<int, string>  $codes
     */
    private function grantDeliveryLockFeatures(
        array $codes = [
            DeliveryLockFeatures::VIEW,
            DeliveryLockFeatures::POSTPONE,
            DeliveryLockFeatures::RELEASE,
        ],
        ?User $user = null,
    ): void {
        $user ??= $this->user;

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Lock Role '.Str::random(4),
            'code' => 'LOCK_ROLE_'.Str::random(4),
            'status' => 'active',
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);

        foreach ($codes as $code) {
            // Feature codes are unique — a second grant reuses the row and only
            // adds the role_permissions entry for the new role.
            $featureId = DB::table('app_module_features')->where('code', $code)->value('id')
                ?? DB::table('app_module_features')->insertGetId([
                    'app_module_id' => 10008,
                    'name' => $code.' '.Str::random(4),
                    'code' => $code,
                    'status' => 'active',
                ]);

            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'app_module_feature_id' => $featureId,
                'is_allowed' => true,
            ]);
        }
    }

    private function lockUrl(Voucher $voucher): string
    {
        return '/api/freights/'.$voucher->id.'/lock';
    }

    private function listUrl(array $params = []): string
    {
        $query = http_build_query($params);

        return '/api/freights/delivery_note'.($query ? "?{$query}" : '');
    }

    public function test_dispatch_detail_saves_and_returns_sender_freight_values(): void
    {
        $note = $this->createDeliveryNote();
        $dispatch = $note->voucher_dispatch_detail;

        $this->withToken($this->token)
            ->putJson('/api/voucher_dispatch_details/'.$dispatch->id, [
                'id' => $dispatch->id,
                'voucher_id' => $note->id,
                'sender_rate' => 125.50,
                'sender_freight_charges' => 1255.00,
            ])
            ->assertOk()
            ->assertJsonPath('data.senderRate', '125.50')
            ->assertJsonPath('data.senderFreightCharges', '1255.00');

        $this->assertDatabaseHas('voucher_dispatch_details', [
            'voucher_id' => $note->id,
            'sender_rate' => 125.50,
            'sender_freight_charges' => 1255.00,
        ]);
    }

    public function test_lock_records_reason_and_note(): void
    {
        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), [
                'reason' => 'vehicle_failure',
                'note' => 'Vehicle broke down on NH-33',
            ])
            ->assertOk()
            ->assertJsonPath('data.deliveryLocked', true)
            ->assertJsonPath('data.deliveryLockReason', 'vehicle_failure')
            ->assertJsonPath('data.deliveryLockNote', 'Vehicle broke down on NH-33')
            // Blameable: the lock records who postponed the delivery.
            ->assertJsonPath('data.deliveryLockedBy', $this->user->id)
            ->assertJsonPath('data.deliveryLockedByName', $this->user->name);

        $this->assertDatabaseHas('delivery_locks', [
            'voucher_id' => $note->id,
            'reason' => 'vehicle_failure',
            'note' => 'Vehicle broke down on NH-33',
            'is_locked' => true,
            'unlocked_at' => null,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_lock_is_allowed_after_freight_is_prepared(): void
    {
        $this->attachFiscalYear();

        $note = $this->createDeliveryNote(['totalFare' => 1500]);
        $this->createPreparedFreight($note);

        // Fare entered + freight voucher linked = "prepared" — the lock must
        // still be accepted (vehicle failed after the bill was raised).
        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), ['reason' => 'uncertain_situation'])
            ->assertOk()
            ->assertJsonPath('data.deliveryLocked', true)
            ->assertJsonPath('data.deliveryLockReason', 'uncertain_situation');

        // Prepared filter still finds it (indicator only — nothing hidden).
        $prepared = collect($this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'prepared']))
            ->assertOk()
            ->json('data'))->pluck('id')->all();
        expect($prepared)->toContain($note->id);

        // And the new Postponed filter finds it too.
        $locked = collect($this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'locked']))
            ->assertOk()
            ->json('data'))->pluck('id')->all();
        expect($locked)->toContain($note->id);
    }

    public function test_lock_requires_a_preset_reason(): void
    {
        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), ['reason' => 'vehicle_broke_down_maybe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertDatabaseMissing('delivery_locks', ['voucher_id' => $note->id]);
    }

    public function test_note_is_optional_when_locking(): void
    {
        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), ['reason' => 'other'])
            ->assertOk()
            ->assertJsonPath('data.deliveryLocked', true)
            ->assertJsonPath('data.deliveryLockReason', 'other');
    }

    public function test_unlock_clears_the_lock(): void
    {
        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), [
                'reason' => 'vehicle_failure',
                'note' => 'Tyre burst',
            ])
            ->assertOk();

        $unlocked = $this->withToken($this->token)
            ->deleteJson($this->lockUrl($note))
            ->assertOk()
            ->assertJsonPath('data.deliveryLocked', false)
            ->assertJsonPath('data.deliveryLockReason', null)
            ->assertJsonPath('data.latestDeliveryLock.reason', 'vehicle_failure')
            ->assertJsonPath('data.latestDeliveryLock.note', 'Tyre burst')
            // Blameable: the release moment + who released it.
            ->assertJsonPath('data.deliveryUnlockedByName', $this->user->name)
            ->assertJsonPath('data.latestDeliveryLock.unlockedByName', $this->user->name);

        expect($unlocked->json('data.deliveryUnlockedAt'))->not->toBeNull();

        // The row is closed, not deleted — the lock history stays auditable.
        $this->assertDatabaseHas('delivery_locks', [
            'voucher_id' => $note->id,
            'is_locked' => false,
            'updated_by' => $this->user->id,
        ]);
        expect(DB::table('delivery_locks')->where('voucher_id', $note->id)->value('unlocked_at'))
            ->not->toBeNull();
    }

    public function test_postponed_filter_returns_only_locked_deliveries(): void
    {
        $this->attachFiscalYear();

        $locked = $this->createDeliveryNote(['voucherNo' => 'DLNT-LOCKED']);
        $this->createDeliveryNote(['voucherNo' => 'DLNT-OPEN']);
        $this->withToken($this->token)
            ->postJson($this->lockUrl($locked), ['reason' => 'party_unavailable'])
            ->assertOk();

        $postponed = collect($this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'locked']))
            ->assertOk()
            ->json('data'));

        expect($postponed)->toHaveCount(1)
            ->and($postponed[0]['voucherNo'])->toBe('DLNT-LOCKED');

        // 'postponed' is accepted as an alias for the same filter.
        $alias = collect($this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'postponed']))
            ->assertOk()
            ->json('data'));
        expect($alias)->toHaveCount(1);

        // 'all' still lists both — the lock is an indicator, not a hide.
        $all = collect($this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'all']))
            ->assertOk()
            ->json('data'));
        expect($all)->toHaveCount(2);

        // The report-specific filter includes released locks but excludes
        // deliveries that never had a lock.
        $this->withToken($this->token)
            ->deleteJson($this->lockUrl($locked))
            ->assertOk();

        $history = $this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'all', 'lock_history' => 'true']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $locked->id)
            ->assertJsonPath('data.0.deliveryLocked', false)
            ->assertJsonPath('data.0.latestDeliveryLock.reason', 'party_unavailable');
    }

    public function test_single_text_search_matches_dispatch_fields(): void
    {
        $this->attachFiscalYear();

        $matching = $this->createDeliveryNote([
            'carrierName' => 'North Star Logistics',
            'vehicleNo' => 'JH01XY9876',
            'source' => 'RANCHI',
            'destination' => 'DALTONGANJ',
        ]);
        $this->createDeliveryNote([
            'carrierName' => 'South Roadways',
            'vehicleNo' => 'BR02AB1234',
            'source' => 'PATNA',
            'destination' => 'GAYA',
        ]);

        foreach (['North Star', 'XY9876', 'RANCHI', 'DALTONGANJ'] as $term) {
            $this->withToken($this->token)
                ->getJson($this->listUrl(['search' => $term, 'freight_status' => 'all']))
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', $matching->id);
        }
    }

    public function test_single_text_search_matches_secondary_destination(): void
    {
        $this->attachFiscalYear();

        $matching = $this->createDeliveryNote([
            'source' => 'RANCHI',
            'destination' => 'DALTONGANJ',
        ]);
        VoucherDispatchDetail::where('voucher_id', $matching->id)
            ->update(['destination_secondary' => 'LATEHAR']);
        $this->createDeliveryNote(['source' => 'PATNA', 'destination' => 'GAYA']);

        $this->withToken($this->token)
            ->getJson($this->listUrl(['search' => 'LATEHAR', 'freight_status' => 'all']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_postponed_count_aggregate_is_reported(): void
    {
        $this->attachFiscalYear();

        $first = $this->createDeliveryNote();
        $second = $this->createDeliveryNote();
        $this->createDeliveryNote();

        foreach ([$first, $second] as $note) {
            $this->withToken($this->token)
                ->postJson($this->lockUrl($note), ['reason' => 'vehicle_failure'])
                ->assertOk();
        }

        // The counter ignores the freight_status toggle so it stays stable
        // while the user flips between Pending/Prepared/Postponed.
        foreach (['prepared', 'locked', 'all'] as $status) {
            $this->withToken($this->token)
                ->getJson($this->listUrl(['freight_status' => $status]))
                ->assertOk()
                ->assertJsonPath('aggregates.postponed_count', 2);
        }
    }

    public function test_delivery_locks_endpoint_locks_and_unlocks(): void
    {
        $this->attachFiscalYear();

        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson('/api/delivery-locks', [
                'id' => $note->id,
                'lock' => true,
                'payload' => [
                    'reason' => 'uncertain_situation',
                    'note' => 'Waiting for the party to confirm',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reason', 'uncertain_situation')
            ->assertJsonPath('data.isLocked', true);

        $this->assertDatabaseHas('delivery_locks', [
            'voucher_id' => $note->id,
            'reason' => 'uncertain_situation',
            'is_locked' => true,
        ]);

        $this->withToken($this->token)
            ->postJson('/api/delivery-locks', [
                'id' => $note->id,
                'lock' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.isLocked', false);

        // Lock state is read back from the active row only.
        $this->withToken($this->token)
            ->getJson($this->listUrl(['freight_status' => 'locked']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_lock_and_unlock_accept_explicit_moments(): void
    {
        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson('/api/delivery-locks', [
                'id' => $note->id,
                'lock' => true,
                'payload' => [
                    'reason' => 'vehicle_failure',
                    'locked_at' => '2026-10-01T09:30:00',
                ],
            ])
            ->assertOk()
            // Wall-clock echo — the exact value the table stores, not UTC.
            ->assertJsonPath('data.lockedAt', '2026-10-01T09:30:00');

        $this->withToken($this->token)
            ->postJson('/api/delivery-locks', [
                'id' => $note->id,
                'lock' => false,
                'payload' => ['unlocked_at' => '2026-10-03T16:45:00'],
            ])
            ->assertOk()
            ->assertJsonPath('data.unlockedAt', '2026-10-03T16:45:00');

        $this->assertDatabaseHas('delivery_locks', [
            'voucher_id' => $note->id,
            'is_locked' => false,
            'locked_at' => '2026-10-01 09:30:00',
            'unlocked_at' => '2026-10-03 16:45:00',
        ]);
    }

    public function test_delivery_locks_endpoint_requires_a_reason_to_lock(): void
    {
        $note = $this->createDeliveryNote();

        $this->withToken($this->token)
            ->postJson('/api/delivery-locks', ['id' => $note->id, 'lock' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload.reason']);
    }

    public function test_lock_rejects_non_delivery_note_vouchers(): void
    {
        $other = Voucher::create([
            'voucher_no' => 'PAY-'.Str::random(5),
            'voucher_date' => now(),
            'voucher_type_id' => 1002,
            'fiscal_year_id' => 1,
            'company_id' => 1,
            'status' => 'active',
        ]);

        $this->withToken($this->token)
            ->postJson($this->lockUrl($other), ['reason' => 'vehicle_failure'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only delivery notes can be locked or unlocked.');
    }

    public function test_lock_returns_404_for_unknown_voucher(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/freights/999999/lock', ['reason' => 'vehicle_failure'])
            ->assertStatus(404);
    }

    /** A user with no delivery-lock role cannot postpone or release. */
    public function test_lock_and_unlock_require_the_feature_permissions(): void
    {
        $outsider = User::create([
            'name' => 'No Lock Rights',
            'email' => 'no-lock-rights@example.com',
            'password' => 'password',
        ]);
        $token = JWTAuth::fromUser($outsider);

        $note = $this->createDeliveryNote();

        $this->withToken($token)
            ->postJson($this->lockUrl($note), ['reason' => 'vehicle_failure'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('delivery_locks', ['voucher_id' => $note->id]);

        $this->withToken($token)
            ->deleteJson($this->lockUrl($note))
            ->assertStatus(403);

        $this->withToken($token)
            ->postJson('/api/delivery-locks', [
                'id' => $note->id,
                'lock' => true,
                'payload' => ['reason' => 'vehicle_failure'],
            ])
            ->assertStatus(403);
    }

    /** Viewing the state is not the same as being allowed to change it. */
    public function test_view_only_permission_cannot_postpone(): void
    {
        $viewer = User::create([
            'name' => 'Lock Viewer',
            'email' => 'lock-viewer@example.com',
            'password' => 'password',
        ]);
        $this->grantDeliveryLockFeatures([DeliveryLockFeatures::VIEW], $viewer);
        $token = JWTAuth::fromUser($viewer);

        $note = $this->createDeliveryNote();

        $this->withToken($token)
            ->postJson($this->lockUrl($note), ['reason' => 'vehicle_failure'])
            ->assertStatus(403);

        $this->withToken($token)
            ->postJson('/api/delivery-locks', [
                'id' => $note->id,
                'lock' => true,
                'payload' => ['reason' => 'vehicle_failure'],
            ])
            ->assertStatus(403);
    }

    /** The lock state itself needs DELIVERY_LOCK_VIEW, not just the action. */
    public function test_lock_state_is_hidden_without_the_view_permission(): void
    {
        $editor = User::create([
            'name' => 'Lock Editor Only',
            'email' => 'lock-editor-only@example.com',
            'password' => 'password',
        ]);
        $this->grantDeliveryLockFeatures([DeliveryLockFeatures::POSTPONE], $editor);
        $token = JWTAuth::fromUser($editor);

        $note = $this->createDeliveryNote();

        // The action is allowed …
        $response = $this->withToken($token)
            ->postJson($this->lockUrl($note), ['reason' => 'vehicle_failure'])
            ->assertOk();

        // …but the payload carries no lock information for this role.
        $response->assertJsonMissingPath('data.deliveryLocked')
            ->assertJsonMissingPath('data.deliveryLockReason');

        // A user holding VIEW still reads it.
        $this->withToken($this->token)
            ->postJson($this->lockUrl($note), ['reason' => 'vehicle_failure'])
            ->assertOk()
            ->assertJsonPath('data.deliveryLocked', true);
    }

    /** Postpone and release are separate rights. */
    public function test_postpone_permission_alone_cannot_release(): void
    {
        $postponer = User::create([
            'name' => 'Lock Postponer',
            'email' => 'lock-postponer@example.com',
            'password' => 'password',
        ]);
        $this->grantDeliveryLockFeatures(
            [DeliveryLockFeatures::VIEW, DeliveryLockFeatures::POSTPONE],
            $postponer,
        );
        $token = JWTAuth::fromUser($postponer);

        $note = $this->createDeliveryNote();

        $this->withToken($token)
            ->postJson($this->lockUrl($note), ['reason' => 'vehicle_failure'])
            ->assertOk();

        $this->withToken($token)
            ->deleteJson($this->lockUrl($note))
            ->assertStatus(403);

        // The same split applies to the toggle endpoint.
        $this->withToken($token)
            ->postJson('/api/delivery-locks', ['id' => $note->id, 'lock' => false])
            ->assertStatus(403);
    }
}
