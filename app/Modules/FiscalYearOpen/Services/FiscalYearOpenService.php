<?php

namespace App\Modules\FiscalYearOpen\Services;

use App\Modules\FiscalYear\Models\FiscalYear;
use App\Modules\FiscalYearOpen\Contracts\FiscalYearOpenServiceInterface;
use App\Modules\StockJournal\Contracts\StockJournalServiceInterface;
use App\Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use App\Modules\UserFiscalYear\Models\UserFiscalYear;
use App\Modules\Voucher\Models\Voucher;
use App\Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use App\Modules\VoucherType\Models\VoucherType;
use App\Enums\MovementType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FiscalYearOpenService implements FiscalYearOpenServiceInterface
{
    public function __construct(
        protected VoucherEntryServiceInterface $voucherEntryService,
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
    ) {}

    public function preview(int $newFiscalYearId, int $previousFiscalYearId): array
    {
        $newFy = FiscalYear::findOrFail($newFiscalYearId);
        $prevFy = FiscalYear::findOrFail($previousFiscalYearId);

        if (!$prevFy->closed_at) {
            throw new \Exception("Previous Fiscal Year '{$prevFy->name}' must be closed before opening a new one.");
        }

        // Get closing balances from previous FY
        $closingVoucher = Voucher::where('fiscal_year_id', $previousFiscalYearId)
            ->whereHas('voucher_type', fn($q) => $q->where('code', 'CLSAC'))
            ->with('voucher_entries.account_ledger.account_group.account_nature')
            ->first();

        $closingStockVoucher = Voucher::where('fiscal_year_id', $previousFiscalYearId)
            ->whereHas('voucher_type', fn($q) => $q->where('code', 'CLSSK'))
            ->with('stock_journal.stock_journal_entries.stock_item', 'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown')
            ->first();

        // Compute balance sheet ledgers from voucher entries (excluding P&L)
        $balanceSheetLedgers = [];
        if ($closingVoucher) {
            foreach ($closingVoucher->voucher_entries as $entry) {
                $ledger = $entry->account_ledger;
                if (!$ledger) continue;

                $nature = $ledger->account_group?->account_nature;
                if ($nature && in_array($nature->code, ['ASSET', 'LIABILITY', 'EQUITY'])) {
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
                $godownEntries = $entry->stock_journal_godown_entries->map(fn($ge) => [
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

        if (!$prevFy->closed_at) {
            throw new \Exception("Previous Fiscal Year '{$prevFy->name}' must be closed before opening a new one.");
        }

        if (!$newFy->isActive()) {
            throw new \Exception("New Fiscal Year '{$newFy->name}' must be active.");
        }

        // Check if already opened
        $existingOpening = Voucher::where('fiscal_year_id', $newFiscalYearId)
            ->whereHas('voucher_type', fn($q) => $q->whereIn('code', ['OPNAC', 'OPNSK']))
            ->exists();

        if ($existingOpening) {
            throw new \Exception("Fiscal Year '{$newFy->name}' already has opening vouchers.");
        }

        DB::beginTransaction();
        try {
            // Step 1: Create Opening Account Voucher
            $openingAccountVoucherType = VoucherType::where('code', 'OPNAC')->firstOrFail();
            $openingAccountVoucher = $this->createOpeningAccountVoucher($newFy, $prevFy, $openingAccountVoucherType);

            // Step 2: Create Opening Stock Voucher
            $openingStockVoucherType = VoucherType::where('code', 'OPNSK')->firstOrFail();
            $openingStockVoucher = $this->createOpeningStockVoucher($newFy, $prevFy, $openingStockVoucherType);

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
                'opening_account_voucher_id' => $openingAccountVoucher->id,
                'opening_stock_voucher_id' => $openingStockVoucher->id,
                'new_fiscal_year_id' => $newFy->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FiscalYearOpen failed: ' . $e->getMessage(), [
                'new_fy_id' => $newFiscalYearId,
                'prev_fy_id' => $previousFiscalYearId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function createOpeningAccountVoucher(FiscalYear $newFy, FiscalYear $prevFy, VoucherType $voucherType): Voucher
    {
        // Get closing balances from previous FY's closing account voucher
        $closingVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
            ->whereHas('voucher_type', fn($q) => $q->where('code', 'CLSAC'))
            ->with('voucher_entries.account_ledger.account_group.account_nature')
            ->first();

        if (!$closingVoucher) {
            throw new \Exception("No closing account voucher found for Fiscal Year '{$prevFy->name}'.");
        }

        // Create opening voucher in the new FY
        $openingVoucher = Voucher::create([
            'voucher_no' => 'OPNAC-' . $newFy->id . '-' . now()->format('Ymd'),
            'voucher_date' => $newFy->start_date,
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $newFy->id,
            'remarks' => "Opening account balances carried forward from {$prevFy->name}",
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => false,
            'module' => 'system',
        ]);

        $entryOrder = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($closingVoucher->voucher_entries as $entry) {
            $ledger = $entry->account_ledger;
            if (!$ledger) continue;

            $nature = $ledger->account_group?->account_nature;
            if (!$nature) continue;

            // Only carry forward Balance Sheet ledgers
            if (!in_array($nature->code, ['ASSET', 'LIABILITY', 'EQUITY', 'CAPITAL'])) {
                continue;
            }

            $entryOrder++;
            $balance = ($entry->debit ?? 0) - ($entry->credit ?? 0);

            if ($balance == 0) continue;

            if (in_array($nature->code, ['ASSET'])) {
                // Assets have debit balances
                $this->voucherEntryService->store([
                    'voucher_id' => $openingVoucher->id,
                    'entry_order' => $entryOrder,
                    'account_ledger_id' => $ledger->id,
                    'debit' => abs($balance),
                    'credit' => 0,
                    'remarks' => "Opening balance from {$prevFy->name}",
                ]);
                $totalDebit += abs($balance);
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
                $totalCredit += abs($balance);
            }
        }

        if ($entryOrder === 0) {
            throw new \Exception("No balance sheet ledgers found to carry forward from '{$prevFy->name}'.");
        }

        return $openingVoucher;
    }

    protected function createOpeningStockVoucher(FiscalYear $newFy, FiscalYear $prevFy, VoucherType $voucherType): Voucher
    {
        // Get closing stock from previous FY's closing stock voucher
        $closingStockVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
            ->whereHas('voucher_type', fn($q) => $q->where('code', 'CLSSK'))
            ->with('stock_journal.stock_journal_entries.stock_journal_godown_entries')
            ->first();

        if (!$closingStockVoucher?->stock_journal) {
            throw new \Exception("No closing stock voucher found for Fiscal Year '{$prevFy->name}'.");
        }

        // Create StockJournal for opening stock
        $stockJournal = $this->stockJournalService->store([
            'journal_no' => 'OPNSK-' . $newFy->id . '-' . now()->format('Ymd'),
            'journal_date' => $newFy->start_date,
            'type' => 'OPENING',
            'remarks' => "Opening stock carried forward from {$prevFy->name}",
        ]);

        $closingEntries = $closingStockVoucher->stock_journal->stock_journal_entries;

        foreach ($closingEntries as $closingEntry) {
            $godownEntryData = [];
            $godownOrder = 0;

            foreach ($closingEntry->stock_journal_godown_entries as $ge) {
                $godownOrder++;
                $godownEntryData[] = [
                    'entry_order' => $godownOrder,
                    'godown_id' => $ge->godown_id,
                    'actual_quantity' => $ge->actual_quantity,
                    'movement_type' => MovementType::IN->value,
                    'remarks' => "Opening stock from {$prevFy->name}",
                ];
            }

            $this->stockJournalEntryService->store([
                'stock_journal_id' => $stockJournal->id,
                'entry_order' => $closingEntry->entry_order,
                'stock_item_id' => $closingEntry->stock_item_id,
                'stock_unit_id' => $closingEntry->stock_unit_id,
                'actual_quantity' => $closingEntry->actual_quantity,
                'movement_type' => MovementType::IN->value,
                'stock_journal_godown_entries' => $godownEntryData,
            ]);
        }

        // Create Voucher linking to this StockJournal
        $voucher = Voucher::create([
            'voucher_no' => 'OPNSK-' . $newFy->id . '-' . now()->format('Ymd'),
            'voucher_date' => $newFy->start_date,
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $newFy->id,
            'stock_journal_id' => $stockJournal->id,
            'remarks' => "Opening stock carried forward from {$prevFy->name}",
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => false,
            'effects_stock' => true,
            'module' => 'system',
        ]);

        return $voucher;
    }
}
