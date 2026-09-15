<?php

namespace Modules\Voucher\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;
use Modules\Voucher\Services\VoucherService;
use Modules\VoucherParty\Models\VoucherParty;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

/**
 * Regression tests for processPartyUpdateStep.
 *
 * The voucher_party relation is hasOne, so a voucher has at most one party
 * row. The frontend can send a party payload without an id (e.g. the party
 * was re-selected and the form rebuilt the object), and the update pipeline
 * must resolve the existing row via voucher_id and update it — not blindly
 * store a duplicate row, which the hasOne relation never surfaces (the
 * symptom was "saved successfully but nothing changed").
 */
class VoucherPartyUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Voucher $voucher;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Party Update Tester',
            'email' => 'party-update-tester@example.com',
            'password' => 'password',
        ]);
        $this->actingAs($user);

        VoucherType::create([
            'name' => 'Delivery Note',
            'code' => 'DLNT',
            'voucher_category_id' => 1,
            'status' => 'active',
        ]);

        $this->voucher = Voucher::create([
            'voucher_no' => 'DLNT-TEST-1',
            'voucher_date' => '2026-05-17',
            'voucher_type_id' => VoucherType::where('code', 'DLNT')->first()->id,
            'fiscal_year_id' => 1,
            'company_id' => 1,
            'status' => 'active',
        ]);

        // mailing_name is NOT NULL in the schema (yet only 'sometimes' in the
        // request rules, so the validator drops it when absent from a payload).
        VoucherParty::create([
            'voucher_id' => $this->voucher->id,
            'name' => 'ORIGINAL PARTY',
            'mailing_name' => 'ORIGINAL PARTY',
        ]);
    }

    private function updateParty(array $party): void
    {
        $service = app(VoucherService::class);

        // Use reflection: the update step is an internal pipeline step and
        // requires the row lock the full update() path acquires.
        $method = new \ReflectionMethod(VoucherService::class, 'processPartyUpdateStep');
        $method->invoke($service, ['party' => $party], $this->voucher->fresh());
    }

    public function test_updates_existing_party_when_payload_has_no_id(): void
    {
        $this->updateParty([
            'name' => 'JIBON GHOSH GODOWN',
            'mailing_name' => 'JIBON GHOSH GODOWN',
        ]);

        $parties = VoucherParty::where('voucher_id', $this->voucher->id)->get();

        $this->assertCount(1, $parties, 'A duplicate voucher_party row must not be created.');
        $this->assertSame('JIBON GHOSH GODOWN', $parties->first()->name);
    }

    public function test_updates_existing_party_when_payload_has_id(): void
    {
        $existing = VoucherParty::where('voucher_id', $this->voucher->id)->first();

        $this->updateParty([
            'id' => $existing->id,
            'name' => 'RENAMED PARTY',
            'mailing_name' => 'RENAMED PARTY',
        ]);

        $parties = VoucherParty::where('voucher_id', $this->voucher->id)->get();

        $this->assertCount(1, $parties);
        $this->assertSame('RENAMED PARTY', $parties->first()->name);
    }

    public function test_creates_party_when_voucher_has_none(): void
    {
        VoucherParty::where('voucher_id', $this->voucher->id)->delete();

        $this->updateParty([
            'name' => 'NEW PARTY',
            'mailing_name' => 'NEW PARTY',
        ]);

        $this->assertDatabaseHas('voucher_parties', [
            'voucher_id' => $this->voucher->id,
            'name' => 'NEW PARTY',
        ]);
    }
}
