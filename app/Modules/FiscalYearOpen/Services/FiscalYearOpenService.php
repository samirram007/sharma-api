<?php

namespace Modules\FiscalYearOpen\Services;

use App\Support\Traits\HasItemAverageRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\FiscalYearOpen\Contracts\FiscalYearOpenServiceInterface;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherType\Models\VoucherType;

class FiscalYearOpenService implements FiscalYearOpenServiceInterface
{
    use HasItemAverageRate;

    public function __construct(
        protected VoucherEntryServiceInterface $voucherEntryService,
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
    ) {}

    public function preview(int $newFiscalYearId, int $previousFiscalYearId): array
    {
        $newFy = FiscalYear::findOrFail($newFiscalYearId);
        $prevFy = FiscalYear::findOrFail($previousFiscalYearId);

        if (! $prevFy->closed_at) {
            throw new \Exception("Previous Fiscal Year '{$prevFy->name}' must be closed before opening a new one.");
        }

        // Get closing balances from previous FY
        $closingVoucher = Voucher::where('fiscal_year_id', $previousFiscalYearId)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSAC'))
            ->with('voucher_entries.account_ledger.account_group.account_nature')
            ->first();

        $closingStockVoucher = Voucher::where('fiscal_year_id', $previousFiscalYearId)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSSK'))
            ->with('stock_journal.stock_journal_entries.stock_item', 'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown')
            ->first();

        // Compute balance sheet ledgers from voucher entries (excluding P&L)
        $balanceSheetLedgers = [];
        if ($closingVoucher) {
            foreach ($closingVoucher->voucher_entries as $entry) {
                $ledger = $entry->account_ledger;
                if (! $ledger) {
                    continue;
                }

                $nature = $ledger->account_group?->account_nature;
                if ($nature && in_array($nature->code, ['AST', 'LIA', 'EQY'])) {
                    $balanceSheetLedgers[] = [
                        'ledger_id' => $ledger->id,
                        'ledger_name' => $ledger->name,
                        'nature' => $nature->code,
                        'balance' => ($entry->debit ?? 0) - ($entry->credit ?? 0),
                    ];
                }
            }
        }

        // Compute stock items from closing stock voucher
        $stockItems = [];
        if ($closingStockVoucher?->stock_journal) {
            foreach ($closingStockVoucher->stock_journal->stock_journal_entries as $entry) {
                $godownEntries = $entry->stock_journal_godown_entries->map(fn ($ge) => [
                    'godown_id' => $ge->godown_id,
                    'godown_name' => $ge->godown->name ?? null,
                    'quantity' => (float) $ge->actual_quantity,
                ]);
                $stockItems[] = [
                    'item_id' => $entry->stock_item_id,
                    'item_name' => $entry->stock_item->name ?? null,
                    'total_quantity' => (float) $entry->actual_quantity,
                    'godowns' => $godownEntries,
                ];
            }
        }

        return [
            'previous_fiscal_year' => $prevFy->only(['id', 'name', 'start_date', 'end_date']),
            'new_fiscal_year' => $newFy->only(['id', 'name', 'start_date', 'end_date']),
            'balance_sheet_ledgers' => $balanceSheetLedgers,
            'total_ledgers' => count($balanceSheetLedgers),
            'stock_items' => $stockItems,
            'total_stock_items' => count($stockItems),
        ];
    }

    public function open(int $newFiscalYearId, int $previousFiscalYearId): array
    {
        $newFy = FiscalYear::findOrFail($newFiscalYearId);
        $prevFy = FiscalYear::findOrFail($previousFiscalYearId);

        if (! $prevFy->closed_at) {
            throw new \Exception("Previous Fiscal Year '{$prevFy->name}' must be closed before opening a new one.");
        }

        if (! $newFy->isActive()) {
            throw new \Exception("New Fiscal Year '{$newFy->name}' must be active.");
        }

        // Check if already opened
        $existingOpening = Voucher::where('fiscal_year_id', $newFiscalYearId)
            ->whereHas('voucher_type', fn ($q) => $q->whereIn('code', ['OPNJL', 'OPNAC', 'OPNSK']))
            ->exists();

        if ($existingOpening) {
            throw new \Exception("Fiscal Year '{$newFy->name}' already has opening vouchers.");
        }

        DB::beginTransaction();
        try {
            // Step 1: Get opening journal voucher type
            $openingJournalVoucherType = VoucherType::where('code', 'OPNJL')->firstOrFail();

            // Step 2: Create the unified OpeningJournal voucher
            $openingJournalVoucher = $this->createOpeningJournalVoucher($newFy, $prevFy, $openingJournalVoucherType);

            // Step 3: Update all UserFiscalYear records to point to the new FY
            UserFiscalYear::query()->update([
                'fiscal_year_id' => $newFy->id,
                'start_date' => $newFy->start_date,
                'end_date' => $newFy->end_date,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Fiscal Year '{$newFy->name}' opened successfully with opening balances from '{$prevFy->name}'.",
                'opening_journal_voucher_id' => $openingJournalVoucher->id,
                'new_fiscal_year_id' => $newFy->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FiscalYearOpen failed: '.$e->getMessage(), [
                'new_fy_id' => $newFiscalYearId,
                'prev_fy_id' => $previousFiscalYearId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a single unified OpeningJournal voucher that carries forward:
     * 1. Balance Sheet ledger balances (via VoucherEntry)
     * 2. Stock quantities per godown (via StockJournal → StockJournalEntry → StockJournalGodownEntry)
     */
    protected function createOpeningJournalVoucher(FiscalYear $newFy, FiscalYear $prevFy, VoucherType $voucherType): Voucher
    {
        // ---- PART 1: Get closing account data from previous FY ----
        $closingVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSAC'))
            ->with('voucher_entries.account_ledger.account_group.account_nature')
            ->first();

        if (! $closingVoucher) {
            throw new \Exception("No closing account voucher found for Fiscal Year '{$prevFy->name}'.");
        }

        // ---- PART 2: Get closing stock data from previous FY ----
        $closingStockVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSSK'))
            ->with('stock_journal.stock_journal_entries.stock_journal_godown_entries')
            ->first();

        // ---- PART 3: Create the unified OpeningJournal Voucher ----
        $openingVoucher = Voucher::create([
            'voucher_no' => 'OPNJL-'.$newFy->id.'-'.now()->format('Ymd'),
            'voucher_date' => $newFy->start_date,
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $newFy->id,
            'remarks' => "Unified opening entry carrying forward balances and stock from {$prevFy->name} to {$newFy->name}",
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => $closingStockVoucher?->stock_journal ? true : false,
            'module' => 'system',
        ]);

        // ---- PART 4: Create VoucherEntry records for Balance Sheet ledgers ----
        $entryOrder = 0;
        $balanceSheetFound = false;

        foreach ($closingVoucher->voucher_entries as $entry) {
            $ledger = $entry->account_ledger;
            if (! $ledger) {
                continue;
            }

            $nature = $ledger->account_group?->account_nature;
            if (! $nature) {
                continue;
            }

            // Only carry forward Balance Sheet ledgers (exclude P&L which was already transferred to Capital)
            if (! in_array($nature->code, ['AST', 'LIA', 'EQY'])) {
                continue;
            }

            $entryOrder++;
            $balance = ($entry->debit ?? 0) - ($entry->credit ?? 0);

            if ($balance == 0) {
                continue;
            }
            $balanceSheetFound = true;

            if (in_array($nature->code, ['AST'])) {
                // Assets have debit balances
                $this->voucherEntryService->store([
                    'voucher_id' => $openingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $ledger->id,
                    'debit' => abs($balance),
                    'credit' => 0,
                    'remarks' => "Opening balance from {$prevFy->name}",
                ]);
            } else {
                // Liabilities, Equity, Capital have credit balances
                $this->voucherEntryService->store([
                    'voucher_id' => $openingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $ledger->id,
                    'debit' => 0,
                    'credit' => abs($balance),
                    'remarks' => "Opening balance from {$prevFy->name}",
                ]);
            }
        }

        if (! $balanceSheetFound) {
            throw new \Exception("No balance sheet ledgers found to carry forward from '{$prevFy->name}'.");
        }

        // ---- PART 5: Create StockJournal + entries for opening stock ----
        if ($closingStockVoucher?->stock_journal) {
            // Create StockJournal for opening stock
            $stockJournal = $this->stockJournalService->store([
                'journal_no' => 'OPNJL-'.$newFy->id.'-'.now()->format('Ymd'),
                'journal_date' => $newFy->start_date,
                'type' => 'OPENING',
                'remarks' => "Opening stock carried forward from {$prevFy->name} to {$newFy->name}",
            ]);

            // Link the StockJournal to the OpeningJournal voucher
            $openingVoucher->update(['stock_journal_id' => $stockJournal->id]);

            // Create StockJournalEntry records for each item with godown-level detail
            $closingEntries = $closingStockVoucher->stock_journal->stock_journal_entries;

            foreach ($closingEntries as $closingEntry) {
                // Compute weighted average rate from previous FY's purchase/inward stock entries
                $avgRate = $this->getItemAverageRate($closingEntry->stock_item_id, $prevFy->id);
                $totalAmount = round($avgRate * (float) $closingEntry->actual_quantity, 2);

                $godownEntryData = [];
                $godownOrder = 0;

                foreach ($closingEntry->stock_journal_godown_entries as $ge) {
                    $godownOrder++;
                    $qty = (float) $ge->actual_quantity;
                    $godownAmount = round($avgRate * $qty, 2);

                    $godownEntryData[] = [
                        'entry_order' => $godownOrder,
                        'godown_id' => $ge->godown_id,
                        'actual_quantity' => $qty,
                        'billing_quantity' => $qty,
                        'rate' => $avgRate > 0 ? $avgRate : null,
                        'amount' => $godownAmount,
                        'movement_type' => $ge->movement_type->value,
                        'remarks' => "Opening stock from {$prevFy->name}",
                    ];
                }

                $this->stockJournalEntryService->store([
                    'stock_journal_id' => $stockJournal->id,
                    'entry_order' => $closingEntry->entry_order,
                    'stock_item_id' => $closingEntry->stock_item_id,
                    'stock_unit_id' => $closingEntry->stock_unit_id,
                    'actual_quantity' => $closingEntry->actual_quantity,
                    'billing_quantity' => $closingEntry->actual_quantity,
                    'rate' => $avgRate > 0 ? $avgRate : null,
                    'amount' => $totalAmount,
                    'movement_type' => $closingEntry->movement_type->value,
                    'stock_journal_godown_entries' => $godownEntryData,
                ]);
            }
        }

        return $openingVoucher->fresh();
    }
}
