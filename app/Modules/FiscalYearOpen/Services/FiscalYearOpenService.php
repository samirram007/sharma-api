<?php

namespace Modules\FiscalYearOpen\Services;

use App\Enums\MovementType;
use App\Support\Services\BaseService;
use App\Support\Traits\HasItemAverageRate;
use App\Support\Traits\ScopesCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\FiscalYearOpen\Contracts\FiscalYearOpenServiceInterface;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherType\Models\VoucherType;

class FiscalYearOpenService extends BaseService implements FiscalYearOpenServiceInterface
{
    use HasItemAverageRate;
    use ScopesCompany;

    protected string $modelClass = FiscalYear::class;

    public function __construct(
        protected VoucherEntryServiceInterface $voucherEntryService,
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
        protected UserFiscalYearServiceInterface $userFiscalYearService,
        protected StockSummaryServiceInterface $stockSummaryService,
    ) {}

    public function preview(int $newFiscalYearId, int $previousFiscalYearId): array
    {
        $newFy = FiscalYear::findOrFail($newFiscalYearId);
        $prevFy = FiscalYear::findOrFail($previousFiscalYearId);

        $this->validateCompanyAccess($newFy);
        $this->validateCompanyAccess($prevFy);

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
                        'ledgerId' => $ledger->id,
                        'ledgerName' => $ledger->name,
                        'nature' => $nature->code,
                        'balance' => ($entry->debit ?? 0) - ($entry->credit ?? 0),
                    ];
                }
            }
        }

        // Compute stock items from closing stock voucher.
        $stockItems = [];
        $stockSource = 'closing_journal';

        if ($closingStockVoucher?->stock_journal) {
            foreach ($closingStockVoucher->stock_journal->stock_journal_entries as $entry) {
                $godownEntries = $entry->stock_journal_godown_entries->map(fn ($ge) => [
                    'godownId' => $ge->godown_id,
                    'godownName' => $ge->godown->name ?? null,
                    'quantity' => (float) $ge->actual_quantity,
                    'batchNo' => $ge->batch_no,
                    'mfgDate' => $ge->mfg_date?->toDateString(),
                    'expiryDate' => $ge->expiry_date?->toDateString(),
                ]);
                $stockItems[] = [
                    'itemId' => $entry->stock_item_id,
                    'itemName' => $entry->stock_item->name ?? null,
                    'totalQuantity' => (float) $entry->actual_quantity,
                    'godowns' => $godownEntries,
                ];
            }
        } else {
            // No frozen CLSSK closing journal — fall back to the previous FY's
            // RUNNING balance (same computation the fiscal year close uses to
            // freeze closing stock), so the preview still shows stock items.
            $stockSource = 'running';
            $runningItems = $this->stockSummaryService
                ->runningClosingStockItems($prevFy->id, $prevFy->end_date);

            foreach ($runningItems as $item) {
                $godowns = [];
                foreach ($item['godown_details'] ?? [] as $godownDetail) {
                    $batches = $godownDetail['batch_details'] ?? [];

                    // Expand one godown row per batch (godown entries carry
                    // their own batch info), mirroring the CLSSK shape.
                    if (empty($batches)) {
                        $batches = [['quantity' => $godownDetail['closing_quantity'] ?? 0]];
                    }

                    foreach ($batches as $batch) {
                        $qty = (float) ($batch['quantity'] ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }

                        $godowns[] = [
                            'godownId' => $godownDetail['godown_id'],
                            'godownName' => $godownDetail['godown_name'] ?? null,
                            'quantity' => $qty,
                            'batchNo' => $batch['batch_no'] ?? null,
                            'mfgDate' => $batch['mfg_date'] ?? null,
                            'expiryDate' => $batch['expiry_date'] ?? null,
                        ];
                    }
                }

                if (empty($godowns)) {
                    continue;
                }

                $stockItems[] = [
                    'itemId' => (int) $item['item_id'],
                    'itemName' => $item['item_name'] ?? null,
                    'totalQuantity' => (float) $item['closing_quantity'],
                    'godowns' => $godowns,
                ];
            }
        }

        return [
            'previousFiscalYear' => [
                'id' => $prevFy->id,
                'name' => $prevFy->name,
                'startDate' => $prevFy->start_date?->toDateString(),
                'endDate' => $prevFy->end_date?->toDateString(),
            ],
            'newFiscalYear' => [
                'id' => $newFy->id,
                'name' => $newFy->name,
                'startDate' => $newFy->start_date?->toDateString(),
                'endDate' => $newFy->end_date?->toDateString(),
            ],
            'balanceSheetLedgers' => $balanceSheetLedgers,
            'totalLedgers' => count($balanceSheetLedgers),
            'stockSource' => $stockSource,
            'stockItems' => $stockItems,
            'totalStockItems' => count($stockItems),
        ];
    }

    public function open(int $newFiscalYearId, int $previousFiscalYearId, array $stockItems = []): array
    {
        $newFy = FiscalYear::findOrFail($newFiscalYearId);
        $prevFy = FiscalYear::findOrFail($previousFiscalYearId);

        $this->validateCompanyAccess($newFy);
        $this->validateCompanyAccess($prevFy);

        if (! $prevFy->closed_at) {
            throw new \Exception("Previous Fiscal Year '{$prevFy->name}' must be closed before opening a new one.");
        }

        if (! $newFy->isActive()) {
            throw new \Exception("New Fiscal Year '{$newFy->name}' must be active.");
        }

        // Check if already opened (pessimistic lock mirrors OpeningBalanceService
        // and prevents concurrent requests from double-opening the same FY).
        $existingOpening = Voucher::where('fiscal_year_id', $newFiscalYearId)
            ->whereHas('voucher_type', fn ($q) => $q->whereIn('code', ['OPNJL', 'OPNAC', 'OPNSK']))
            ->lockForUpdate()
            ->exists();

        if ($existingOpening) {
            throw new \Exception("Fiscal Year '{$newFy->name}' already has opening vouchers.");
        }

        DB::beginTransaction();
        try {
            // Step 1: Get opening journal voucher type
            $openingJournalVoucherType = VoucherType::where('code', 'OPNJL')->firstOrFail();

            // Step 2: Create the unified OpeningJournal voucher
            // `$stockItems` carries the user-edited opening stock quantities
            // (item → godown → batch) from the preview screen. When provided,
            // they override the frozen previous-FY closing journal.
            $openingJournalVoucher = $this->createOpeningJournalVoucher($newFy, $prevFy, $openingJournalVoucherType, $stockItems);

            // Step 3: Update UserFiscalYear records to point to the new FY (scoped to this company)
            $companyId = $newFy->company_id;
            UserFiscalYear::whereHas('fiscal_year', fn ($q) => $q->where('company_id', $companyId))
                ->update([
                    'fiscal_year_id' => $newFy->id,
                    'start_date' => $newFy->start_date,
                    'end_date' => $newFy->end_date,
                ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Fiscal Year '{$newFy->name}' opened successfully with opening balances from '{$prevFy->name}'.",
                'openingJournalVoucherId' => $openingJournalVoucher->id,
                'newFiscalYearId' => $newFy->id,
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
     *
     * When `$stockItems` (user-edited quantities from the preview screen) is
     * non-empty, the opening stock journal is built from those overrides instead
     * of the frozen previous-FY closing stock journal.
     */
    protected function createOpeningJournalVoucher(FiscalYear $newFy, FiscalYear $prevFy, VoucherType $voucherType, array $stockItems = []): Voucher
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

        // User-edited stock overrides win over the frozen closing journal. When
        // a payload is present it is authoritative even if every quantity is 0
        // (the user deliberately zeroed out opening stock).
        $hasStockOverrides = ! empty($stockItems);
        $hasStockOverride = $hasStockOverrides && $this->hasNonZeroStockOverride($stockItems);

        // ---- PART 3: Create the unified OpeningJournal Voucher ----
        $openingVoucher = Voucher::create([
            'voucher_no' => 'OPNJL-'.$newFy->id.'-'.now()->format('Ymd'),
            'voucher_date' => $newFy->start_date,
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => $newFy->id,
            'company_id' => $newFy->company_id,
            'remarks' => "Unified opening entry carrying forward balances and stock from {$prevFy->name} to {$newFy->name}",
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => true,
            'effects_stock' => $hasStockOverrides
                ? $hasStockOverride
                : (bool) $closingStockVoucher?->stock_journal,
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
        // An explicit (even all-zero) stock payload always uses the override
        // path; without one we fall back to the frozen closing journal.
        if ($hasStockOverrides) {
            $this->createOpeningStockJournalFromOverrides($stockItems, $newFy, $prevFy, $openingVoucher);
        } elseif ($closingStockVoucher?->stock_journal) {
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
                        'batch_no' => $ge->batch_no,
                        'mfg_date' => $ge->mfg_date?->toDateString(),
                        'expiry_date' => $ge->expiry_date?->toDateString(),
                        'actual_quantity' => $qty,
                        'billing_quantity' => $qty,
                        'rate' => $avgRate,
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
                    'rate' => $avgRate,
                    'amount' => $totalAmount,
                    'movement_type' => $closingEntry->movement_type->value,
                    'stock_journal_godown_entries' => $godownEntryData,
                ]);
            }
        }

        return $openingVoucher->fresh();
    }

    /**
     * Does the user-edited stock payload contain at least one non-zero quantity?
     */
    protected function hasNonZeroStockOverride(array $stockItems): bool
    {
        foreach ($stockItems as $item) {
            $total = 0;
            foreach (($item['godowns'] ?? []) as $ge) {
                $total += (float) ($ge['quantity'] ?? 0);
            }

            if ($total > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create the opening StockJournal from user-edited stock quantities
     * (item → godown → batch) instead of the frozen previous-FY closing journal.
     *
     * The payload mirrors the preview shape returned by preview():
     *   [
     *     'item_id' => 5,
     *     'godowns' => [
     *       ['godown_id' => 2, 'quantity' => 10, 'batch_no' => 'B1',
     *        'mfg_date' => null, 'expiry_date' => null],
     *     ],
     *   ]
     */
    protected function createOpeningStockJournalFromOverrides(array $stockItems, FiscalYear $newFy, FiscalYear $prevFy, Voucher $openingVoucher): void
    {
        $preparedEntries = [];
        $entryOrder = 0;

        foreach ($stockItems as $itemData) {
            $itemId = (int) ($itemData['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $item = StockItem::find($itemId);
            if (! $item) {
                continue;
            }

            $godownRows = [];
            $godownOrder = 0;
            $totalQty = 0;

            foreach (($itemData['godowns'] ?? []) as $ge) {
                $qty = (float) ($ge['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $godownOrder++;
                $totalQty += $qty;
                $godownRows[] = [
                    'entry_order' => $godownOrder,
                    'godown_id' => (int) ($ge['godown_id'] ?? 0),
                    'batch_no' => $ge['batch_no'] ?? null,
                    'mfg_date' => $ge['mfg_date'] ?? null,
                    'expiry_date' => $ge['expiry_date'] ?? null,
                    'actual_quantity' => $qty,
                    'billing_quantity' => $qty,
                    'rate' => 0,
                    'amount' => 0,
                    'movement_type' => MovementType::IN->value,
                    'remarks' => "Opening stock from {$prevFy->name}",
                ];
            }

            if ($totalQty <= 0) {
                continue;
            }

            // Weighted average inward rate from the previous FY (same valuation
            // the frozen closing journal path uses). Brand-new items with no
            // purchase history fall back to 0 — the rate column is NOT NULL.
            $avgRate = $this->getItemAverageRate($itemId, $prevFy->id);
            $totalAmount = round($avgRate * $totalQty, 2);

            foreach ($godownRows as &$row) {
                $row['rate'] = $avgRate;
                $row['amount'] = round($avgRate * (float) $row['actual_quantity'], 2);
            }
            unset($row);

            $entryOrder++;
            $preparedEntries[] = [
                'entry_order' => $entryOrder,
                'stock_item_id' => $itemId,
                'stock_unit_id' => $item->stock_unit_id,
                'actual_quantity' => $totalQty,
                'billing_quantity' => $totalQty,
                'rate' => $avgRate,
                'amount' => $totalAmount,
                'movement_type' => MovementType::IN->value,
                'stock_journal_godown_entries' => $godownRows,
            ];
        }

        if (empty($preparedEntries)) {
            return;
        }

        $stockJournal = $this->stockJournalService->store([
            'journal_no' => 'OPNJL-'.$newFy->id.'-'.now()->format('Ymd'),
            'journal_date' => $newFy->start_date,
            'type' => 'OPENING',
            'remarks' => "Opening stock carried forward from {$prevFy->name} to {$newFy->name}",
        ]);

        $openingVoucher->update(['stock_journal_id' => $stockJournal->id]);

        foreach ($preparedEntries as $entry) {
            $entry['stock_journal_id'] = $stockJournal->id;
            $this->stockJournalEntryService->store($entry);
        }
    }
}
