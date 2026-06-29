<?php

namespace App\Modules\FiscalYearClose\Services;

use App\Enums\MovementType;
use App\Modules\AccountLedger\Models\AccountLedger;
use App\Modules\FiscalYear\Models\FiscalYear;
use App\Modules\FiscalYearClose\Contracts\FiscalYearCloseServiceInterface;
use App\Modules\StockJournal\Contracts\StockJournalServiceInterface;
use App\Modules\StockJournal\Models\StockJournal;
use App\Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use App\Modules\Voucher\Models\Voucher;
use App\Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use App\Modules\VoucherType\Models\VoucherType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FiscalYearCloseService implements FiscalYearCloseServiceInterface
{
    public function __construct(
        protected VoucherEntryServiceInterface $voucherEntryService,
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
    ) {}

    public function preview(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Count all vouchers in this FY
        $totalVouchers = Voucher::where('fiscal_year_id', $fiscalYearId)->count();

        // Count ledger balances
        $ledgerBalances = DB::table('voucher_entries')
            ->join('vouchers', 'voucher_entries.voucher_id', '=', 'vouchers.id')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->selectRaw('
                account_ledger_id,
                SUM(debit) as total_debit,
                SUM(credit) as total_credit
            ')
            ->groupBy('account_ledger_id')
            ->get();

        // Count stock items with movement
        $stockItems = DB::table('stock_journal_entries')
            ->join('stock_journals', 'stock_journal_entries.stock_journal_id', '=', 'stock_journals.id')
            ->join('vouchers', 'stock_journals.id', '=', 'vouchers.stock_journal_id')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->distinct('stock_journal_entries.stock_item_id')
            ->count('stock_journal_entries.stock_item_id');

        // Count godowns with stock
        $godownsWithStock = DB::table('stock_journal_godown_entries')
            ->join('stock_journal_entries', 'stock_journal_godown_entries.stock_journal_entry_id', '=', 'stock_journal_entries.id')
            ->join('stock_journals', 'stock_journal_entries.stock_journal_id', '=', 'stock_journals.id')
            ->join('vouchers', 'stock_journals.id', '=', 'vouchers.stock_journal_id')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->distinct('stock_journal_godown_entries.godown_id')
            ->count('stock_journal_godown_entries.godown_id');

        return [
            'fiscal_year' => $fiscalYear->only(['id', 'name', 'start_date', 'end_date']),
            'total_vouchers' => $totalVouchers,
            'total_ledgers_with_balance' => $ledgerBalances->count(),
            'total_stock_items' => $stockItems,
            'total_godowns' => $godownsWithStock,
            'is_closed' => $fiscalYear->closed_at !== null,
        ];
    }

    public function close(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        if ($fiscalYear->closed_at) {
            throw new \Exception("Fiscal Year '{$fiscalYear->name}' is already closed.");
        }

        if (!$fiscalYear->isActive()) {
            throw new \Exception("Fiscal Year '{$fiscalYear->name}' is not active.");
        }

        DB::beginTransaction();
        try {
            // Step 1: Create Closing Account Voucher
            $closingAccountVoucherType = VoucherType::where('code', 'CLSAC')->firstOrFail();
            $closingAccountVoucher = $this->createClosingAccountVoucher($fiscalYear, $closingAccountVoucherType);

            // Step 2: Create Closing Stock Voucher
            $closingStockVoucherType = VoucherType::where('code', 'CLSSK')->firstOrFail();
            $closingStockVoucher = $this->createClosingStockVoucher($fiscalYear, $closingStockVoucherType);

            // Step 3: Mark FY as closed
            $fiscalYear->update([
                'closed_at' => now(),
                'closed_by' => Auth::id(),
                'status' => 'inactive',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Fiscal Year '{$fiscalYear->name}' closed successfully.",
                'closing_account_voucher_id' => $closingAccountVoucher->id,
                'closing_stock_voucher_id' => $closingStockVoucher->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FiscalYearClose failed: ' . $e->getMessage(), [
                'fiscal_year_id' => $fiscalYearId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function reopen(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        if (!$fiscalYear->closed_at) {
            throw new \Exception("Fiscal Year '{$fiscalYear->name}' is not closed.");
        }

        DB::beginTransaction();
        try {
            // Delete closing vouchers (stock journal entries cascade)
            $closingVouchers = Voucher::where('fiscal_year_id', $fiscalYearId)
                ->whereHas('voucher_type', function ($q) {
                    $q->whereIn('code', ['CLSAC', 'CLSSK']);
                })
                ->get();

            foreach ($closingVouchers as $voucher) {
                // Delete voucher entries
                $voucher->voucher_entries()->delete();

                // Delete stock journal if exists
                if ($voucher->stock_journal_id) {
                    $stockJournal = StockJournal::find($voucher->stock_journal_id);
                    if ($stockJournal) {
                        $stockJournal->stock_journal_entries()->each(function ($entry) {
                            $entry->stock_journal_godown_entries()->delete();
                            $entry->delete();
                        });
                        $stockJournal->delete();
                    }
                }

                $voucher->delete();
            }

            // Restore FY status
            $fiscalYear->update([
                'closed_at' => null,
                'closed_by' => null,
                'status' => 'active',
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Fiscal Year '{$fiscalYear->name}' has been reopened.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FiscalYearReopen failed: ' . $e->getMessage(), [
                'fiscal_year_id' => $fiscalYearId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function createClosingAccountVoucher(FiscalYear $fiscalYear, VoucherType $voucherType): Voucher
    {
        // Step 1: Compute net balance per ledger within this FY
        $ledgerBalances = DB::table('voucher_entries')
            ->join('vouchers', 'voucher_entries.voucher_id', '=', 'vouchers.id')
            ->where('vouchers.fiscal_year_id', $fiscalYear->id)
            ->selectRaw('
                voucher_entries.account_ledger_id,
                ROUND(SUM(COALESCE(voucher_entries.debit, 0)) - SUM(COALESCE(voucher_entries.credit, 0)), 2) as net_balance
            ')
            ->groupBy('voucher_entries.account_ledger_id')
            ->having('net_balance', '!=', 0)
            ->get();

        if ($ledgerBalances->isEmpty()) {
            throw new \Exception("No ledger balances to close for Fiscal Year '{$fiscalYear->name}'.");
        }

        // Step 2: Identify P&L ledgers (Revenue/Expense nature) vs Balance Sheet ledgers (Asset/Liability)
        $plLedgers = [];   // P&L ledgers to transfer to capital
        $bsLedgers = [];   // Balance sheet ledgers (recorded for audit trail)

        // Get a Capital/Retained Earnings ledger (use first equity account found)
        $capitalLedger = AccountLedger::whereHas('account_group.account_nature', function ($q) {
            $q->whereIn('code', ['EQUITY', 'CAPITAL']);
        })->first();

        foreach ($ledgerBalances as $lb) {
            $ledger = AccountLedger::with('account_group.account_nature')->find($lb->account_ledger_id);
            if (!$ledger) continue;

            $nature = $ledger->account_group?->account_nature;
            $natureCode = $nature?->code;

            if (in_array($natureCode, ['REVENUE', 'EXPENSE', 'INCOME'])) {
                $plLedgers[] = $lb;
            } else {
                $bsLedgers[] = $lb;
            }
        }

        // Step 3: Create the closing account voucher
        $closingVoucher = Voucher::create([
            'voucher_no' => 'CLS-' . $fiscalYear->id . '-' . now()->format('Ymd'),
            'voucher_date' => $fiscalYear->end_date,
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $fiscalYear->id,
            'remarks' => "Account closing for fiscal year {$fiscalYear->name}",
            'status' => 'active',
            'is_effecting' => false,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'system',
        ]);

        $entryOrder = 0;

        // Create transfer entries from P&L ledgers to Capital
        foreach ($plLedgers as $lb) {
            $netBalance = (float) $lb->net_balance;
            $entryOrder++;

            if ($netBalance > 0) {
                // Net debit balance → credit to P&L, debit to Capital
                $this->voucherEntryService->store([
                    'voucher_id' => $closingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $lb->account_ledger_id,
                    'credit' => $netBalance,
                    'debit' => 0,
                    'remarks' => "Closing transfer to Capital",
                ]);
            } else {
                // Net credit balance → debit to P&L, credit to Capital
                $this->voucherEntryService->store([
                    'voucher_id' => $closingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $lb->account_ledger_id,
                    'debit' => abs($netBalance),
                    'credit' => 0,
                    'remarks' => "Closing transfer from Capital",
                ]);
            }
        }

        // Add offsetting entry to Capital account
        $totalPLBalance = collect($plLedgers)->sum(fn($lb) => (float) $lb->net_balance);
        $entryOrder++;

        if ($capitalLedger) {
            if ($totalPLBalance > 0) {
                // Net profit → Credit to Capital
                $this->voucherEntryService->store([
                    'voucher_id' => $closingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $capitalLedger->id,
                    'credit' => $totalPLBalance,
                    'debit' => 0,
                    'remarks' => "Net profit transferred from P&L for {$fiscalYear->name}",
                ]);
            } else {
                // Net loss → Debit to Capital
                $this->voucherEntryService->store([
                    'voucher_id' => $closingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $capitalLedger->id,
                    'debit' => abs($totalPLBalance),
                    'credit' => 0,
                    'remarks' => "Net loss transferred from P&L for {$fiscalYear->name}",
                ]);
            }
        }

        return $closingVoucher;
    }

    protected function createClosingStockVoucher(FiscalYear $fiscalYear, VoucherType $voucherType): Voucher
    {
        // Step 1: Compute stock quantities per item per godown within this FY
        $stockData = DB::table('stock_journal_godown_entries')
            ->join('stock_journal_entries', 'stock_journal_godown_entries.stock_journal_entry_id', '=', 'stock_journal_entries.id')
            ->join('stock_journals', 'stock_journal_entries.stock_journal_id', '=', 'stock_journals.id')
            ->join('vouchers', 'stock_journals.id', '=', 'vouchers.stock_journal_id')
            ->where('vouchers.fiscal_year_id', $fiscalYear->id)
            ->selectRaw('
                stock_journal_entries.stock_item_id,
                stock_journal_godown_entries.godown_id,
                stock_journal_entries.stock_unit_id,
                SUM(CASE
                    WHEN stock_journal_godown_entries.movement_type = ? THEN stock_journal_godown_entries.actual_quantity
                    ELSE -stock_journal_godown_entries.actual_quantity
                END) as net_quantity
            ', [MovementType::IN->value])
            ->groupBy('stock_journal_entries.stock_item_id', 'stock_journal_godown_entries.godown_id', 'stock_journal_entries.stock_unit_id')
            ->having('net_quantity', '!=', 0)
            ->get();

        if ($stockData->isEmpty()) {
            throw new \Exception("No stock quantities to close for Fiscal Year '{$fiscalYear->name}'.");
        }

        // Step 2: Create StockJournal for closing
        $stockJournal = $this->stockJournalService->store([
            'journal_no' => 'CLSSK-' . $fiscalYear->id . '-' . now()->format('Ymd'),
            'journal_date' => $fiscalYear->end_date,
            'type' => 'CLOSING',
            'remarks' => "Stock closing for fiscal year {$fiscalYear->name}",
        ]);

        // Step 3: Group by stock_item_id for StockJournalEntry
        $groupedByItem = $stockData->groupBy('stock_item_id');

        $entryOrder = 0;
        foreach ($groupedByItem as $itemId => $godownEntries) {
            $entryOrder++;
            $totalQty = $godownEntries->sum('net_quantity');
            $stockUnitId = $godownEntries->first()->stock_unit_id;

            // Create a single entry per item with godown-wise details
            $godownEntryData = [];
            $godownOrder = 0;

            foreach ($godownEntries as $ge) {
                $godownOrder++;
                $godownEntryData[] = [
                    'entry_order' => $godownOrder,
                    'godown_id' => $ge->godown_id,
                    'actual_quantity' => $ge->net_quantity,
                    'movement_type' => MovementType::IN->value,
                    'remarks' => "Closing stock - {$fiscalYear->name}",
                ];
            }

            $this->stockJournalEntryService->store([
                'stock_journal_id' => $stockJournal->id,
                'entry_order' => $entryOrder,
                'stock_item_id' => $itemId,
                'stock_unit_id' => $stockUnitId,
                'actual_quantity' => abs($totalQty),
                'movement_type' => MovementType::IN->value,
                'stock_journal_godown_entries' => $godownEntryData,
            ]);
        }

        // Step 4: Create Voucher linking to this StockJournal
        $voucher = Voucher::create([
            'voucher_no' => 'CLSSK-' . $fiscalYear->id . '-' . now()->format('Ymd'),
            'voucher_date' => $fiscalYear->end_date,
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $fiscalYear->id,
            'stock_journal_id' => $stockJournal->id,
            'remarks' => "Stock closing for fiscal year {$fiscalYear->name}",
            'status' => 'active',
            'is_effecting' => false,
            'effects_account' => false,
            'effects_stock' => true,
            'module' => 'system',
        ]);

        return $voucher;
    }
}
