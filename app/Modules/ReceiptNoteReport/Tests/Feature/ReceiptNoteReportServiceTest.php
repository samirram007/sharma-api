<?php

namespace Modules\ReceiptNoteReport\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AccountGroup\Models\AccountGroup;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountNature\Models\AccountNature;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

class ReceiptNoteReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private FiscalYear $fiscalYear;

    private VoucherType $receiptNoteType;

    private AccountNature $assetNature;

    private AccountGroup $assetGroup;

    protected function setUp(): void
    {
        parent::setUp();

        // Fiscal year
        $this->fiscalYear = FiscalYear::create([
            'name' => 'FY 2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);

        // User with UserFiscalYear
        $this->user = User::factory()->create();
        UserFiscalYear::create([
            'user_id' => $this->user->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'current_date' => '2025-04-01',
        ]);
        $this->actingAs($this->user);

        // Receipt Note voucher type (id=2002 as used in the service)
        $this->receiptNoteType = VoucherType::create([
            'id' => 2002,
            'name' => 'Receipt Note',
            'code' => 'RCPT',
            'status' => 'active',
        ]);

        // Account nature & group
        $this->assetNature = AccountNature::create(['name' => 'Asset', 'code' => 'AST', 'accounting_effect' => 'debit']);
        $this->assetGroup = AccountGroup::create(['name' => 'Current Assets', 'account_nature_id' => $this->assetNature->id, 'code' => 'CA']);
    }

    /**
     * Create a receipt note voucher with a single ledger entry.
     */
    private function createReceiptNoteVoucher(array $overrides = []): Voucher
    {
        $ledger = AccountLedger::create([
            'name' => 'Receivables',
            'code' => 'REC001',
            'account_group_id' => $this->assetGroup->id,
        ]);

        $voucher = Voucher::create(array_merge([
            'voucher_no' => 'RCPT-1',
            'voucher_date' => '2025-04-15',
            'voucher_type_id' => $this->receiptNoteType->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Test receipt note',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'accounts',
        ], $overrides));

        VoucherEntry::create([
            'voucher_id' => $voucher->id,
            'entry_order' => 1,
            'account_ledger_id' => $ledger->id,
            'debit' => 5000,
            'credit' => 0,
            'remarks' => 'Receipt entry',
        ]);

        return $voucher;
    }

    // ──────────────────────────────────────────────
    //  ReceiptNoteReportService::getAll() — uses attachLedgerInfo
    // ──────────────────────────────────────────────

    public function test_get_all_returns_receipt_notes_with_ledger_info(): void
    {
        $this->createReceiptNoteVoucher();

        $response = $this->getJson('/api/receipt_note_reports?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'voucher_no',
                        'amount',
                    ],
                ],
                'status',
                'code',
                'message',
            ]);
    }

    public function test_get_all_filters_by_receipt_note_type_only(): void
    {
        // Create a receipt note (type 2002)
        $this->createReceiptNoteVoucher(['voucher_no' => 'RCPT-001']);

        // Create a non-receipt-note voucher (different type — should be excluded)
        $otherType = VoucherType::create(['name' => 'Payment', 'code' => 'PAYM', 'status' => 'active']);
        $ledger = AccountLedger::create(['name' => 'Bank', 'code' => 'BNK001', 'account_group_id' => $this->assetGroup->id]);
        $otherVoucher = Voucher::create([
            'voucher_no' => 'PAYM-001',
            'voucher_date' => '2025-04-15',
            'voucher_type_id' => $otherType->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Payment',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'accounts',
        ]);
        VoucherEntry::create([
            'voucher_id' => $otherVoucher->id,
            'entry_order' => 1,
            'account_ledger_id' => $ledger->id,
            'debit' => 2000,
            'credit' => 0,
        ]);

        $response = $this->getJson('/api/receipt_note_reports?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('RCPT-001', $response->json('data.data.0.voucher_no'));
    }

    public function test_get_all_returns_empty_when_no_receipt_notes(): void
    {
        $response = $this->getJson('/api/receipt_note_reports?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_get_all_returns_correct_amount(): void
    {
        $this->createReceiptNoteVoucher();

        $response = $this->getJson('/api/receipt_note_reports?per_page=10');

        $response->assertStatus(200);
        $this->assertSame(5000, (int) $response->json('data.data.0.amount'));
    }

    public function test_get_all_filters_by_search_term(): void
    {
        $this->createReceiptNoteVoucher(['voucher_no' => 'RCPT-001', 'remarks' => 'Goods received']);

        $response = $this->getJson('/api/receipt_note_reports?per_page=10&search=Goods');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_get_all_paginates_correctly(): void
    {
        // Create 3 receipt notes
        for ($i = 1; $i <= 3; $i++) {
            $this->createReceiptNoteVoucher(['voucher_no' => "RCPT-{$i}"]);
        }

        $response = $this->getJson('/api/receipt_note_reports?per_page=2');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));
        $this->assertSame(3, $response->json('data.total'));
    }

    // ──────────────────────────────────────────────
    //  Edge Cases
    // ──────────────────────────────────────────────

    public function test_get_all_handles_multiple_receipt_notes(): void
    {
        $this->createReceiptNoteVoucher(['voucher_no' => 'RCPT-001']);
        $this->createReceiptNoteVoucher(['voucher_no' => 'RCPT-002']);

        $response = $this->getJson('/api/receipt_note_reports?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_get_all_excludes_vouchers_outside_fiscal_year(): void
    {
        $this->createReceiptNoteVoucher([
            'voucher_no' => 'RCPT-001',
            'voucher_date' => '2024-03-31',
            'fiscal_year_id' => 999,
        ]);

        $response = $this->getJson('/api/receipt_note_reports?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));
    }
}
