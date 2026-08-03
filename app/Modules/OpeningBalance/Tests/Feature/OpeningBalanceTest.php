<?php

namespace Modules\OpeningBalance\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AccountGroup\Models\AccountGroup;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountNature\Models\AccountNature;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\OpeningBalance\Facades\OpeningBalanceFacade;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

class OpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private FiscalYear $fiscalYear;

    private VoucherType $opnJalVoucherType;

    private AccountNature $assetNature;

    private AccountNature $liabilityNature;

    private AccountNature $equityNature;

    private AccountGroup $assetGroup;

    private AccountGroup $liabilityGroup;

    private AccountGroup $equityGroup;

    private Godown $godown;

    private StockUnit $stockUnit;

    private StockItem $stockItem;

    protected function setUp(): void
    {
        parent::setUp();

        // ── Fiscal Year ──
        $this->fiscalYear = FiscalYear::create([
            'name' => 'FY 2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);

        // ── User with UserFiscalYear ──
        $this->user = User::factory()->create();
        UserFiscalYear::create([
            'user_id' => $this->user->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
        ]);
        $this->actingAs($this->user);

        // ── VoucherType (OPNJL) ──
        $this->opnJalVoucherType = VoucherType::create([
            'name' => 'Opening Journal',
            'code' => 'OPNJL',
            'status' => 'active',
        ]);

        // ── Account Natures & Groups ──
        $this->assetNature = AccountNature::create(['name' => 'Asset', 'code' => 'AST', 'accounting_effect' => 'debit']);
        $this->liabilityNature = AccountNature::create(['name' => 'Liability', 'code' => 'LIA', 'accounting_effect' => 'credit']);
        $this->equityNature = AccountNature::create(['name' => 'Equity', 'code' => 'EQY', 'accounting_effect' => 'credit']);

        $this->assetGroup = AccountGroup::create(['name' => 'Current Assets', 'account_nature_id' => $this->assetNature->id, 'code' => 'CA']);
        $this->liabilityGroup = AccountGroup::create(['name' => 'Current Liabilities', 'account_nature_id' => $this->liabilityNature->id, 'code' => 'CL']);
        $this->equityGroup = AccountGroup::create(['name' => 'Capital Account', 'account_nature_id' => $this->equityNature->id, 'code' => 'CAP']);

        // ── Godown & StockUnit & StockItem ──
        $this->godown = Godown::create(['name' => 'Main Warehouse', 'code' => 'WH01']);
        $this->stockUnit = StockUnit::create(['name' => 'Pieces', 'code' => 'PCS', 'no_of_decimal_places' => 2]);
        $this->stockItem = StockItem::create([
            'name' => 'Test Item',
            'stock_unit_id' => $this->stockUnit->id,
            'opening_stock_quantity' => 0,
        ]);
    }

    // ──────────────────────────────────────────────
    //  getSetupData()
    // ──────────────────────────────────────────────

    public function test_get_setup_data_returns_ledgers_and_stock_items(): void
    {
        // Create a ledger for the asset group
        $ledger = AccountLedger::create([
            'name' => 'Cash in Hand',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'current_fiscal_year' => ['id', 'name'],
                    'ledgers',
                    'stock_items',
                    'godowns',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data['ledgers']);
        $this->assertSame('Cash in Hand', $data['ledgers'][0]['ledger_name']);
        $this->assertCount(1, $data['stock_items']);
        $this->assertSame('Test Item', $data['stock_items'][0]['item_name']);
        $this->assertFalse($data['has_existing_opening']);
    }

    public function test_get_setup_data_detects_existing_opening(): void
    {
        $ledger = AccountLedger::create([
            'name' => 'Bank Account',
            'code' => 'BANK001',
            'account_group_id' => $this->assetGroup->id,
        ]);

        // Create an existing OPNJL voucher
        Voucher::create([
            'voucher_no' => 'OPNJL-'.$this->fiscalYear->id.'-test',
            'voucher_date' => '2025-04-01',
            'voucher_type_id' => $this->opnJalVoucherType->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Existing opening',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'system',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.has_existing_opening'));
    }

    // ──────────────────────────────────────────────
    //  getStatus()
    // ──────────────────────────────────────────────

    public function test_get_status_returns_no_opening_when_none_exists(): void
    {
        $response = $this->getJson('/api/opening-balance/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_existing_opening' => false,
                ],
            ]);
    }

    public function test_get_status_returns_opening_when_one_exists(): void
    {
        Voucher::create([
            'voucher_no' => 'OPNJL-'.$this->fiscalYear->id.'-test',
            'voucher_date' => '2025-04-01',
            'voucher_type_id' => $this->opnJalVoucherType->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Test opening',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'system',
        ]);

        $response = $this->getJson('/api/opening-balance/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_existing_opening' => true,
                ],
            ]);
    }

    // ──────────────────────────────────────────────
    //  store() — Pipeline Tests
    // ──────────────────────────────────────────────

    public function test_store_creates_opening_with_ledger_entries(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $response = $this->postJson('/api/opening-balance', [
            'remarks' => 'Test opening entry',
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 10000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 10000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);

        // Verify the voucher was created
        $this->assertDatabaseHas('vouchers', [
            'fiscal_year_id' => $this->fiscalYear->id,
            'voucher_type_id' => $this->opnJalVoucherType->id,
        ]);

        // Verify voucher entries were created
        $voucher = Voucher::where('fiscal_year_id', $this->fiscalYear->id)
            ->where('voucher_type_id', $this->opnJalVoucherType->id)
            ->first();
        $this->assertNotNull($voucher);
        $this->assertDatabaseHas('voucher_entries', [
            'voucher_id' => $voucher->id,
            'account_ledger_id' => $cashLedger->id,
            'debit' => 10000,
        ]);
        $this->assertDatabaseHas('voucher_entries', [
            'voucher_id' => $voucher->id,
            'account_ledger_id' => $capitalLedger->id,
            'credit' => 10000,
        ]);
    }

    public function test_store_creates_opening_with_stock_entries(): void
    {
        $response = $this->postJson('/api/opening-balance', [
            'remarks' => 'Opening stock entry',
            'stock_entries' => [
                [
                    'item_id' => $this->stockItem->id,
                    'godowns' => [
                        ['godown_id' => $this->godown->id, 'quantity' => 50],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);

        // Verify stock journal was created
        $voucher = Voucher::where('fiscal_year_id', $this->fiscalYear->id)
            ->where('voucher_type_id', $this->opnJalVoucherType->id)
            ->first();
        $this->assertNotNull($voucher);
        $this->assertNotNull($voucher->stock_journal_id);
    }

    public function test_store_throws_exception_when_no_entries_provided(): void
    {
        $response = $this->postJson('/api/opening-balance', [
            'remarks' => 'Empty opening',
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('vouchers', 0);
    }

    public function test_store_rejects_duplicate_opening_for_same_fiscal_year(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $payload = [
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 5000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 5000],
            ],
        ];

        // First call — should succeed
        $this->postJson('/api/opening-balance', $payload)->assertStatus(200);

        // Second call — should fail because opening already exists (lockForUpdate prevents race)
        $this->postJson('/api/opening-balance', $payload)->assertStatus(500);

        // Should only have one OPNJL voucher
        $this->assertDatabaseCount('vouchers', 1);
    }

    // ──────────────────────────────────────────────
    //  store() — Concurrent lockForUpdate() Scenarios
    // ──────────────────────────────────────────────

    public function test_store_duplicate_returns_clear_error_message(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $payload = [
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 5000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 5000],
            ],
        ];

        // First call succeeds
        $this->postJson('/api/opening-balance', $payload)->assertStatus(200);

        // Second call should fail with a clear message about the existing opening
        $response = $this->postJson('/api/opening-balance', $payload);
        $response->assertStatus(500);

        $responseData = $response->json();
        $errorMessage = $responseData['message'] ?? $responseData['error'] ?? '';
        $this->assertStringContainsString(
            'already exists',
            $errorMessage,
            'Error message should clearly indicate opening already exists'
        );
    }

    public function test_store_duplicate_leaves_no_partial_data_on_failure(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $payload = [
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 5000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 5000],
            ],
        ];

        // Create the opening
        $this->postJson('/api/opening-balance', $payload)->assertStatus(200);

        // Capture counts before the failed duplicate attempt
        $vouchersBefore = Voucher::count();
        $entriesBefore = VoucherEntry::count();

        // Attempt duplicate — should fail
        $this->postJson('/api/opening-balance', $payload)->assertStatus(500);

        // Verify no additional records were created by the failed request
        $this->assertDatabaseCount('vouchers', $vouchersBefore);
        $this->assertDatabaseCount('voucher_entries', $entriesBefore);
    }

    public function test_store_transaction_rolls_back_on_exception_before_fy_lock_check(): void
    {
        // Test that if an exception occurs BEFORE the lockForUpdate check
        // (e.g., missing entries), no transaction residue remains.
        $response = $this->postJson('/api/opening-balance', [
            'remarks' => 'No entries at all',
            // No ledger_entries or stock_entries — should throw immediately
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('vouchers', 0);
        $this->assertDatabaseCount('voucher_entries', 0);
    }

    public function test_store_explicit_lock_check_via_service_facade(): void
    {
        // Directly test the lockForUpdate + duplicate detection logic
        // through the facade (without HTTP layer).
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $payload = [
            'remarks' => 'Opening via facade',
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 5000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 5000],
            ],
        ];

        // First call via facade — should succeed
        $result = OpeningBalanceFacade::storeOpeningBalance($payload);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('opening_journal_voucher_id', $result);

        // Second call via facade — should throw because opening already exists
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already exists');
        OpeningBalanceFacade::storeOpeningBalance($payload);
    }

    public function test_store_adds_auto_balancing_entry_when_debits_and_credits_differ(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        // Create the "Opening Balance Adjustment" ledger
        $adjustmentGroup = AccountGroup::create([
            'name' => 'Adjustment Account',
            'account_nature_id' => $this->liabilityNature->id,
            'code' => 'ADJ',
        ]);
        AccountLedger::create([
            'name' => 'Opening Balance Adjustment',
            'code' => 'ADJ001',
            'account_group_id' => $adjustmentGroup->id,
        ]);

        // Cash (asset/debit) = 10000, Capital (equity/credit) = 8000 → difference = 2000
        $response = $this->postJson('/api/opening-balance', [
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 10000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 8000],
            ],
        ]);

        $response->assertStatus(200);

        $voucher = Voucher::where('fiscal_year_id', $this->fiscalYear->id)
            ->where('voucher_type_id', $this->opnJalVoucherType->id)
            ->first();

        // Should have 3 entries: cash debit(10000) + capital credit(8000) + adjustment credit(2000)
        $this->assertCount(3, $voucher->voucher_entries);
    }

    public function test_store_creates_voucher_with_correct_remarks(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $this->postJson('/api/opening-balance', [
            'remarks' => 'Custom remarks for opening',
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 5000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 5000],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('vouchers', [
            'fiscal_year_id' => $this->fiscalYear->id,
            'remarks' => 'Custom remarks for opening',
        ]);
    }

    public function test_store_creates_both_ledger_and_stock_entries_in_one_call(): void
    {
        $cashLedger = AccountLedger::create([
            'name' => 'Cash',
            'code' => 'CASH001',
            'account_group_id' => $this->assetGroup->id,
        ]);
        $capitalLedger = AccountLedger::create([
            'name' => 'Capital',
            'code' => 'CAP001',
            'account_group_id' => $this->equityGroup->id,
        ]);

        $response = $this->postJson('/api/opening-balance', [
            'remarks' => 'Combined opening entry',
            'ledger_entries' => [
                ['ledger_id' => $cashLedger->id, 'amount' => 10000],
                ['ledger_id' => $capitalLedger->id, 'amount' => 10000],
            ],
            'stock_entries' => [
                [
                    'item_id' => $this->stockItem->id,
                    'godowns' => [
                        ['godown_id' => $this->godown->id, 'quantity' => 100],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);

        // Verify the voucher has both effects_account and effects_stock
        $this->assertDatabaseHas('vouchers', [
            'fiscal_year_id' => $this->fiscalYear->id,
            'effects_account' => true,
            'effects_stock' => true,
        ]);
    }

    // ──────────────────────────────────────────────
    //  getSetupData() — Previous FY Prefilling
    // ──────────────────────────────────────────────

    public function test_get_setup_data_shows_prev_fy_info_when_closed(): void
    {
        // Create a previous fiscal year that ends before current FY starts
        $prevFy = FiscalYear::create([
            'name' => 'FY 2024-25',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should include previous FY info
        $this->assertNotNull($data['previous_fiscal_year']);
        $this->assertSame('FY 2024-25', $data['previous_fiscal_year']['name']);
        $this->assertTrue($data['previous_fiscal_year']['is_closed']);
    }

    public function test_get_setup_data_no_prev_fy_when_none_exists(): void
    {
        // No previous FY — only the current FY exists
        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNull($data['previous_fiscal_year']);
    }

    public function test_get_setup_data_prefills_ledger_balances_from_clsac_voucher(): void
    {
        // ── Prev FY (closed) ──
        $prevFy = FiscalYear::create([
            'name' => 'FY 2024-25',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);

        // ── Account natures with codes matching the service filter ──
        $assetNature = AccountNature::create([
            'name' => 'Asset', 'code' => 'ASSET', 'accounting_effect' => 'debit',
        ]);
        $liabilityNature = AccountNature::create([
            'name' => 'Liability', 'code' => 'LIABILITY', 'accounting_effect' => 'credit',
        ]);

        // ── Account groups ──
        $assetGroup = AccountGroup::create([
            'name' => 'Fixed Assets', 'account_nature_id' => $assetNature->id, 'code' => 'FA',
        ]);
        $liabilityGroup = AccountGroup::create([
            'name' => 'Long Term Liabilities', 'account_nature_id' => $liabilityNature->id, 'code' => 'LTL',
        ]);

        // ── Ledgers in current FY that will have prefilled balances ──
        $cashLedger = AccountLedger::create([
            'name' => 'Cash in Hand (Prefill)', 'code' => 'CASH_PRE',
            'account_group_id' => $assetGroup->id,
        ]);
        $bankLoanLedger = AccountLedger::create([
            'name' => 'Bank Loan (Prefill)', 'code' => 'LOAN_PRE',
            'account_group_id' => $liabilityGroup->id,
        ]);

        // ── CLSAC VoucherType & Voucher ──
        $clsacType = VoucherType::create([
            'name' => 'Closing Accounts', 'code' => 'CLSAC', 'status' => 'active',
        ]);

        $clsacVoucher = Voucher::create([
            'voucher_no' => 'CLSAC-'.$prevFy->id.'-test',
            'voucher_date' => '2025-03-31',
            'voucher_type_id' => $clsacType->id,
            'fiscal_year_id' => $prevFy->id,
            'remarks' => 'Closing accounts for FY 2024-25',
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'system',
        ]);

        // ── Voucher entries: debit the cash ledger, credit the loan ledger ──
        VoucherEntry::create([
            'voucher_id' => $clsacVoucher->id,
            'entry_order' => 1,
            'account_ledger_id' => $cashLedger->id,
            'debit' => 50000,
            'credit' => 0,
            'remarks' => 'Closing balance forward',
        ]);
        VoucherEntry::create([
            'voucher_id' => $clsacVoucher->id,
            'entry_order' => 2,
            'account_ledger_id' => $bankLoanLedger->id,
            'debit' => 0,
            'credit' => 30000,
            'remarks' => 'Closing balance forward',
        ]);

        // ── A ledger NOT in the closing voucher (should get prefilled_balance = 0) ──
        $noPrefillLedger = AccountLedger::create([
            'name' => 'New Ledger', 'code' => 'NEW001',
            'account_group_id' => $assetGroup->id,
        ]);

        // ── Call API ──
        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // ── Assert previous FY info ──
        $this->assertSame('FY 2024-25', $data['previous_fiscal_year']['name']);
        $this->assertTrue($data['previous_fiscal_year']['is_closed']);

        // ── Assert prefilled_balance values ──
        $cashPrefill = collect($data['ledgers'])->firstWhere('ledger_id', $cashLedger->id);
        $this->assertNotNull($cashPrefill, 'Cash ledger should appear in setup data');
        $this->assertSame(50000.0, $cashPrefill['prefilled_balance'],
            'Cash (asset/debit) should have prefilled_balance = 50000 (debit - credit)');

        $loanPrefill = collect($data['ledgers'])->firstWhere('ledger_id', $bankLoanLedger->id);
        $this->assertNotNull($loanPrefill, 'Bank loan ledger should appear in setup data');
        $this->assertSame(-30000.0, $loanPrefill['prefilled_balance'],
            'Loan (liability/credit) should have prefilled_balance = -30000 (0 - 30000)');

        $noPrefill = collect($data['ledgers'])->firstWhere('ledger_id', $noPrefillLedger->id);
        $this->assertNotNull($noPrefill, 'New ledger should appear in setup data');
        $this->assertSame(0.0, $noPrefill['prefilled_balance'],
            'New ledger (not in closing voucher) should have prefilled_balance = 0');
    }

    public function test_get_setup_data_prefills_stock_quantities_from_clssk_voucher(): void
    {
        // ── Prev FY (closed) ──
        $prevFy = FiscalYear::create([
            'name' => 'FY 2024-25',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);

        // ── CLSSK VoucherType & Voucher ──
        $clsskType = VoucherType::create([
            'name' => 'Closing Stock', 'code' => 'CLSSK', 'status' => 'active',
        ]);

        $clsskVoucher = Voucher::create([
            'voucher_no' => 'CLSSK-'.$prevFy->id.'-test',
            'voucher_date' => '2025-03-31',
            'voucher_type_id' => $clsskType->id,
            'fiscal_year_id' => $prevFy->id,
            'remarks' => 'Closing stock for FY 2024-25',
            'status' => 'active',
            'is_effecting' => false,
            'effects_account' => false,
            'effects_stock' => true,
            'module' => 'stock',
        ]);

        // ── Stock Journal ──
        $stockJournal = StockJournal::create([
            'journal_no' => 'CLSSTK-'.$prevFy->id.'-test',
            'journal_date' => '2025-03-31',
            'type' => 'CLOSING',
            'remarks' => 'Closing stock entry',
        ]);

        // Link the voucher to the stock journal
        $clsskVoucher->update(['stock_journal_id' => $stockJournal->id]);

        // ── Stock Journal Entry ──
        $stockJournalEntry = StockJournalEntry::create([
            'stock_journal_id' => $stockJournal->id,
            'entry_order' => 1,
            'stock_item_id' => $this->stockItem->id,
            'stock_unit_id' => $this->stockUnit->id,
            'actual_quantity' => 250,
            'movement_type' => 'in',
        ]);

        // ── Godown entries (quantities by warehouse) ──
        StockJournalGodownEntry::create([
            'stock_journal_entry_id' => $stockJournalEntry->id,
            'entry_order' => 1,
            'godown_id' => $this->godown->id,
            'actual_quantity' => 250,
            'movement_type' => 'in',
        ]);

        // ── Call API ──
        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // ── Assert prefilled_quantity for the stock item in the test godown ──
        $itemData = collect($data['stock_items'])->firstWhere('item_id', $this->stockItem->id);
        $this->assertNotNull($itemData, 'Stock item should appear in setup data');

        $godownData = collect($itemData['godowns'])->firstWhere('godown_id', $this->godown->id);
        $this->assertNotNull($godownData, 'Godown should appear in stock item data');
        $this->assertSame(250.0, (float) $godownData['prefilled_quantity'],
            'Stock item should have prefilled_quantity = 250 from CLSSK godown entry');
    }

    public function test_get_setup_data_shows_no_prefill_when_prev_fy_not_closed(): void
    {
        // Previous FY exists but is NOT closed (no closed_at)
        $prevFy = FiscalYear::create([
            'name' => 'FY 2024-25',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'status' => 'active',
            // No closed_at — year is still open
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Previous FY info should show it's not closed
        $this->assertNotNull($data['previous_fiscal_year']);
        $this->assertFalse($data['previous_fiscal_year']['is_closed']);

        // Ledgers should have zero prefilled_balance (no closing voucher processed)
        foreach ($data['ledgers'] as $ledger) {
            $this->assertSame(0.0, (float) $ledger['prefilled_balance'],
                "Ledger '{$ledger['ledger_name']}' should have 0 prefilled when prev FY open");
        }

        // Stock items should have zero prefilled_quantity
        foreach ($data['stock_items'] as $item) {
            foreach ($item['godowns'] as $godown) {
                $this->assertSame(0.0, (float) $godown['prefilled_quantity'],
                    "Item '{$item['item_name']}' godown should have 0 prefilled when prev FY open");
            }
        }
    }

    // ──────────────────────────────────────────────
    //  getSetupData() — Previous FY Edge Cases
    // ──────────────────────────────────────────────

    public function test_get_setup_data_selects_most_recent_prev_fy_when_multiple_exist(): void
    {
        // Create two previous FYs — the service should pick the most recent one
        $olderFy = FiscalYear::create([
            'name' => 'FY 2023-24',
            'start_date' => '2023-04-01',
            'end_date' => '2024-03-31',
            'status' => 'closed',
            'closed_at' => '2024-04-01 00:00:00',
        ]);
        $newerFy = FiscalYear::create([
            'name' => 'FY 2024-25',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should pick the most recent prev FY (FY 2024-25, not FY 2023-24)
        $this->assertNotNull($data['previous_fiscal_year']);
        $this->assertSame('FY 2024-25', $data['previous_fiscal_year']['name'],
            'Should select the most recent previous fiscal year');
        $this->assertTrue($data['previous_fiscal_year']['is_closed']);
    }

    public function test_get_setup_data_ignores_fy_with_end_date_equal_to_current_start(): void
    {
        // Create a FY whose end_date is EXACTLY the current FY's start_date.
        // The query uses `end_date < current_start` (strict less than),
        // so this FY should NOT be detected as a previous FY.
        FiscalYear::create([
            'name' => 'Same Day Boundary',
            'start_date' => '2024-04-01',
            'end_date' => '2025-04-01',  // Same as current FY start_date (2025-04-01)
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should NOT be detected since end_date (2025-04-01) is NOT < start_date (2025-04-01)
        $this->assertNull($data['previous_fiscal_year'],
            'FY with end_date equal to current start should not be considered previous FY');
    }

    public function test_get_setup_data_ignores_fy_that_overlaps_or_is_after_current(): void
    {
        // Create a FY that starts after the current FY
        FiscalYear::create([
            'name' => 'Future FY',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should NOT detect the future FY as previous
        $this->assertNull($data['previous_fiscal_year'],
            'Future FY should not be detected as previous fiscal year');
    }

    public function test_get_setup_data_uses_order_by_end_date_desc_for_multiple_candidates(): void
    {
        // Create three overlapping previous FYs. The service uses
        // `orderBy('end_date', 'desc')` to pick the most recent.
        FiscalYear::create([
            'name' => 'Early FY',
            'start_date' => '2020-01-01',
            'end_date' => '2024-06-30',
            'status' => 'closed',
            'closed_at' => '2024-07-01 00:00:00',
        ]);
        FiscalYear::create([
            'name' => 'Late FY',
            'start_date' => '2024-07-01',
            'end_date' => '2025-03-15',
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);
        FiscalYear::create([
            'name' => 'Latest FY',
            'start_date' => '2024-01-01',
            'end_date' => '2025-03-31',
            'status' => 'closed',
            'closed_at' => '2025-04-01 00:00:00',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should pick "Latest FY" (end_date 2025-03-31 is the most recent < 2025-04-01)
        $this->assertNotNull($data['previous_fiscal_year']);
        $this->assertSame('Latest FY', $data['previous_fiscal_year']['name'],
            'Should pick the FY with the most recent end_date (ordered by end_date desc)');
    }

    public function test_get_setup_data_overlapping_prev_fy_detected_correctly(): void
    {
        // Create a FY that ends before current FY but starts after current FY's start.
        // This tests that the service only looks at end_date, not start_date.
        FiscalYear::create([
            'name' => 'Overlapping End',
            'start_date' => '2026-01-01',  // Starts after current FY (2025-04-01)
            'end_date' => '2026-02-28',    // Ends before current FY end but after start
            'status' => 'closed',
            'closed_at' => '2026-03-01 00:00:00',
        ]);

        $response = $this->getJson('/api/opening-balance/setup-data');

        $response->assertStatus(200);
        $data = $response->json('data');

        // end_date (2026-02-28) is NOT < start_date (2025-04-01), so should NOT be detected
        $this->assertNull($data['previous_fiscal_year'],
            'FY with end_date after current start should not be previous FY');
    }
}
