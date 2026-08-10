<?php

namespace Modules\OpeningBalance\Services;

use App\Enums\MovementType;
use App\Support\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\OpeningBalance\Contracts\OpeningBalanceServiceInterface;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherType\Models\VoucherType;

class OpeningBalanceService extends BaseService implements OpeningBalanceServiceInterface
{
    protected string $modelClass = Voucher::class;

    protected $userFiscalYear;

    public function __construct(
        protected UserFiscalYearServiceInterface $userFiscalYearService,
        protected VoucherEntryServiceInterface $voucherEntryService,
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
        protected StockSummaryServiceInterface $stockSummaryService,
    ) {
        // Auth::id() is null outside HTTP (CLI / queue / tests) — guard so the
        // service can be constructed without an authenticated user.
        $userId = Auth::id();
        $this->userFiscalYear = $userId
            ? $this->userFiscalYearService->getByUserId($userId)
            : null;
    }

    public function getSetupData(): array
    {
        $fy = $this->userFiscalYear->fiscalYear ?? $this->userFiscalYear->fiscal_year;
        $currentFyId = $fy->id;

        // Find previous fiscal year (the one that ends before this one starts)
        $prevFy = FiscalYear::where('end_date', '<', $fy->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

        // Check if previous year is closed and has closing data
        $prevFyClosed = $prevFy && $prevFy->closed_at;
        $prevClosingVoucher = null;
        $prevClosingStockVoucher = null;
        $stockSource = null;
        // item_id => running closing stock item (item → godown → batch tree)
        $runningByItem = collect();

        if ($prevFyClosed) {
            $prevClosingVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
                ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSAC'))
                ->with('voucher_entries.account_ledger.account_group.account_nature')
                ->first();

            $prevClosingStockVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
                ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSSK'))
                ->with('stock_journal.stock_journal_entries.stock_item.stock_unit',
                    'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown')
                ->first();

            if ($prevClosingStockVoucher?->stock_journal) {
                $stockSource = 'closing_journal';
            } else {
                // No frozen CLSSK closing journal — fall back to the previous FY's
                // RUNNING balance (same computation the fiscal year close uses to
                // freeze closing stock), so stock prefills still work when the
                // previous year has movements but no frozen closing stock.
                $stockSource = 'running';
                $runningByItem = collect(
                    $this->stockSummaryService
                        ->runningClosingStockItems($prevFy->id, $prevFy->end_date)
                )->keyBy('item_id');
            }
        }

        // Get all balance sheet ledgers (Asset, Liability, Equity)
        $ledgers = AccountLedger::with([
            'account_group.account_nature',
        ])
            ->whereHas('account_group.account_nature', function ($q) {
                $q->whereIn('code', ['ASSET', 'LIABILITY', 'EQUITY', 'CAPITAL']);
            })
            ->orderBy('name')
            ->get();

        $ledgerData = [];
        foreach ($ledgers as $ledger) {
            $nature = $ledger->account_group?->account_nature;

            // Try to find pre-filled balance from previous FY closing
            $prefilledBalance = 0;
            if ($prevClosingVoucher) {
                $matchingEntry = $prevClosingVoucher->voucher_entries
                    ->firstWhere('account_ledger_id', $ledger->id);
                if ($matchingEntry) {
                    $prefilledBalance = ($matchingEntry->debit ?? 0) - ($matchingEntry->credit ?? 0);
                }
            }

            $ledgerData[] = [
                'ledger_id' => $ledger->id,
                'ledger_name' => $ledger->name,
                'ledger_code' => $ledger->code,
                'nature' => $nature?->code,
                'nature_type' => $nature?->accounting_effect, // 'debit' or 'credit'
                'prefilled_balance' => $prefilledBalance,
            ];
        }

        // Get all stock items with godowns
        $stockItems = StockItem::with(['stock_unit'])
            ->orderBy('name')
            ->get();

        $godowns = Godown::orderBy('name')->get();

        $stockItemData = [];
        foreach ($stockItems as $item) {
            // Try to find pre-filled quantities from previous FY closing stock.
            // The closing stock voucher (CLSSK) stores batch details directly on
            // its godown entries (batch_no / mfg_date / expiry_date), so we can
            // surface a read-only batch reference alongside the prefilled qty.
            $prefilledGodowns = [];
            $prefilledBatches = []; // godown_id => [ batch_no => [...] ]

            if ($prevClosingStockVoucher?->stock_journal) {
                $matchingEntries = $prevClosingStockVoucher->stock_journal->stock_journal_entries
                    ->where('stock_item_id', $item->id);

                foreach ($matchingEntries as $entry) {
                    foreach ($entry->stock_journal_godown_entries as $ge) {
                        $prefilledGodowns[$ge->godown_id] = ($prefilledGodowns[$ge->godown_id] ?? 0) + (float) $ge->actual_quantity;

                        $batchKey = (string) ($ge->batch_no ?: '');
                        $prefilledBatches[$ge->godown_id][$batchKey] = [
                            'batch_no' => $ge->batch_no,
                            'mfg_date' => $ge->mfg_date?->toDateString(),
                            'expiry_date' => $ge->expiry_date?->toDateString(),
                            'quantity' => ($prefilledBatches[$ge->godown_id][$batchKey]['quantity'] ?? 0) + (float) $ge->actual_quantity,
                        ];
                    }
                }
            } elseif ($runningItem = $runningByItem->get($item->id)) {
                // Running-balance fallback: map the item → godown → batch tree
                // into the same prefilled shape the CLSSK path produces, so the
                // wizard can pre-fill quantities (and batch references) from the
                // previous FY's live stock balance.
                foreach ($runningItem['godown_details'] as $godownDetail) {
                    $batches = $godownDetail['batch_details'] ?? [];

                    if (empty($batches)) {
                        $batches = [['quantity' => $godownDetail['closing_quantity'] ?? 0]];
                    }

                    foreach ($batches as $batch) {
                        $qty = (float) ($batch['quantity'] ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }

                        $prefilledGodowns[$godownDetail['godown_id']] = ($prefilledGodowns[$godownDetail['godown_id']] ?? 0) + $qty;

                        $batchKey = (string) ($batch['batch_no'] ?: '');
                        $prefilledBatches[$godownDetail['godown_id']][$batchKey] = [
                            'batch_no' => $batch['batch_no'] ?? null,
                            'mfg_date' => $batch['mfg_date'] ?? null,
                            'expiry_date' => $batch['expiry_date'] ?? null,
                            'quantity' => ($prefilledBatches[$godownDetail['godown_id']][$batchKey]['quantity'] ?? 0) + $qty,
                        ];
                    }
                }
            }

            $godownData = [];
            foreach ($godowns as $godown) {
                $godownData[] = [
                    'godown_id' => $godown->id,
                    'godown_name' => $godown->name,
                    'prefilled_quantity' => $prefilledGodowns[$godown->id] ?? 0,
                    'batches' => array_values($prefilledBatches[$godown->id] ?? []),
                ];
            }

            $stockItemData[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_code' => $item->stock_unit?->code,
                'unit_name' => $item->stock_unit?->name,
                'no_of_decimal_places' => $item->stock_unit?->no_of_decimal_places ?? 2,
                'godowns' => $godownData,
            ];
        }

        // Check if opening already exists for this FY
        $existingOpening = Voucher::where('fiscal_year_id', $currentFyId)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'OPNJL'))
            ->exists();

        return [
            'current_fiscal_year' => [
                'id' => $fy->id,
                'name' => $fy->name,
                'start_date' => $fy->start_date,
                'end_date' => $fy->end_date,
            ],
            'previous_fiscal_year' => $prevFy ? [
                'id' => $prevFy->id,
                'name' => $prevFy->name,
                'is_closed' => (bool) $prevFy->closed_at,
            ] : null,
            'has_existing_opening' => $existingOpening,
            'ledgers' => $ledgerData,
            'total_ledgers' => count($ledgerData),
            'stock_source' => $stockSource,
            'stock_items' => $stockItemData,
            'total_stock_items' => count($stockItemData),
            'godowns' => $godowns->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'code' => $g->code,
            ]),
        ];
    }

    /**
     * Store opening balance entries through the processing pipeline.
     *
     * Uses DB::transaction with auto-retry (5 attempts) and pessimistic locking
     * on the OPNJL voucher row to prevent duplicate opening entries under
     * concurrent requests.
     */
    public function storeOpeningBalance(array $data): array
    {
        $fy = $this->userFiscalYear->fiscalYear ?? $this->userFiscalYear->fiscal_year;
        $currentFyId = $fy->id;

        $ledgerEntries = $data['ledger_entries'] ?? [];
        $stockEntries = $data['stock_entries'] ?? [];

        if (empty($ledgerEntries) && empty($stockEntries)) {
            throw new \Exception('At least one ledger entry or stock entry is required.');
        }

        return DB::transaction(function () use ($data, $ledgerEntries, $stockEntries, $fy, $currentFyId) {
            // Pipeline Step 1: Verify no existing opening (with pessimistic lock)
            $this->ensureNoExistingOpeningStep($currentFyId, $fy);

            // Pipeline Step 2: Create the OPNJL voucher
            $openingVoucher = $this->createOpeningVoucherStep($data, $fy, $currentFyId, $ledgerEntries, $stockEntries);

            // Pipeline Step 3: Create voucher entries for ledger balances
            $this->processLedgerEntriesStep($ledgerEntries, $openingVoucher, $fy);

            // Pipeline Step 4: Auto-balance debits and credits
            $this->autoBalanceStep($ledgerEntries, $openingVoucher);

            // Pipeline Step 5: Create stock journal with stock entries
            $this->processStockEntriesStep($stockEntries, $fy, $currentFyId, $openingVoucher);

            return [
                'success' => true,
                'message' => 'Opening balance created successfully.',
                'opening_journal_voucher_id' => $openingVoucher->id,
                'voucher_no' => $openingVoucher->voucher_no,
            ];
        }, 5);
    }

    // ──────────────────────────────────────────────
    //  Pipeline Steps — Store
    // ──────────────────────────────────────────────

    /**
     * Step 1: Verify no existing opening balance for this fiscal year.
     * Uses lockForUpdate() to prevent concurrent creation attempts.
     */
    protected function ensureNoExistingOpeningStep(int $currentFyId, $fy): void
    {
        $existingOpening = Voucher::where('fiscal_year_id', $currentFyId)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'OPNJL'))
            ->lockForUpdate()
            ->exists();

        if ($existingOpening) {
            throw new \Exception(
                "Opening balance already exists for Fiscal Year '{$fy->name}'. Please edit the existing opening journal instead."
            );
        }
    }

    /**
     * Step 2: Create the OPNJL voucher record.
     */
    protected function createOpeningVoucherStep(array $data, $fy, int $currentFyId, array $ledgerEntries, array $stockEntries): Voucher
    {
        $openingJournalVoucherType = VoucherType::where('code', 'OPNJL')->firstOrFail();

        return Voucher::create([
            'voucher_no' => 'OPNJL-'.$currentFyId.'-'.now()->format('YmdHis'),
            'voucher_date' => $fy->start_date,
            'voucher_type_id' => $openingJournalVoucherType->id,
            'fiscal_year_id' => $currentFyId,
            'remarks' => $data['remarks'] ?? "Manual opening balance entry for {$fy->name}",
            'status' => 'active',
            'is_effecting' => true,
            'effects_account' => ! empty($ledgerEntries),
            'effects_stock' => ! empty($stockEntries),
            'module' => 'system',
        ]);
    }

    /**
     * Step 3: Create voucher entries for each ledger balance.
     */
    protected function processLedgerEntriesStep(array $ledgerEntries, Voucher $openingVoucher, $fy): void
    {
        $entryOrder = 0;

        foreach ($ledgerEntries as $entry) {
            $ledger = AccountLedger::with('account_group.account_nature')
                ->find($entry['ledger_id']);
            if (! $ledger) {
                continue;
            }

            $amount = (float) ($entry['amount'] ?? 0);
            if ($amount == 0) {
                continue;
            }

            $entryOrder++;
            $nature = $ledger->account_group?->account_nature;
            $isDebitNature = $nature && $nature->accounting_effect === 'debit';

            $this->voucherEntryService->store([
                'voucher_id' => $openingVoucher->id,
                'entry_order' => $entryOrder,
                'account_ledger_id' => $ledger->id,
                'debit' => $isDebitNature ? abs($amount) : 0,
                'credit' => $isDebitNature ? 0 : abs($amount),
                'remarks' => "Opening balance for {$fy->name}",
            ]);
        }
    }

    /**
     * Step 4: Auto-balance debits and credits by adding an adjustment entry.
     */
    protected function autoBalanceStep(array $ledgerEntries, Voucher $openingVoucher): void
    {
        if (empty($ledgerEntries)) {
            return;
        }

        // Reload with entries to get fresh data
        $openingVoucher->load('voucher_entries');

        $totalDebit = $openingVoucher->voucher_entries->sum('debit');
        $totalCredit = $openingVoucher->voucher_entries->sum('credit');
        $diff = $totalDebit - $totalCredit;

        if (abs($diff) <= 0.001) {
            return;
        }

        $adjustmentLedger = AccountLedger::where('name', 'Opening Balance Adjustment')->first();

        if (! $adjustmentLedger) {
            throw new \Exception(
                "Opening Balance Adjustment ledger not found. Please ensure a ledger named 'Opening Balance Adjustment' exists (type: Liabilities) to hold the auto-balancing entry."
            );
        }

        $lastEntry = $openingVoucher->voucher_entries->max('entry_order') ?? 0;

        if ($diff > 0) {
            // Excess debit → credit the adjustment account
            $this->voucherEntryService->store([
                'voucher_id' => $openingVoucher->id,
                'entry_order' => $lastEntry + 1,
                'account_ledger_id' => $adjustmentLedger->id,
                'debit' => 0,
                'credit' => $diff,
                'remarks' => 'Auto-balancing entry',
            ]);
        } else {
            // Excess credit → debit the adjustment account
            $this->voucherEntryService->store([
                'voucher_id' => $openingVoucher->id,
                'entry_order' => $lastEntry + 1,
                'account_ledger_id' => $adjustmentLedger->id,
                'debit' => abs($diff),
                'credit' => 0,
                'remarks' => 'Auto-balancing entry',
            ]);
        }
    }

    /**
     * Step 5: Create stock journal with godown entries for each stock item.
     */
    protected function processStockEntriesStep(array $stockEntries, $fy, int $currentFyId, Voucher $openingVoucher): void
    {
        if (empty($stockEntries)) {
            return;
        }

        $stockJournal = $this->stockJournalService->store([
            'journal_no' => 'OPNJL-'.$currentFyId.'-'.now()->format('YmdHis'),
            'journal_date' => $fy->start_date,
            'type' => 'OPENING',
            'remarks' => "Manual opening stock for {$fy->name}",
        ]);

        $openingVoucher->update(['stock_journal_id' => $stockJournal->id]);

        $entryOrder = 0;

        foreach ($stockEntries as $stockEntry) {
            $itemId = $stockEntry['item_id'];
            $item = StockItem::find($itemId);
            if (! $item) {
                continue;
            }

            $godownEntryData = [];
            $totalQty = 0;
            $godownOrder = 0;

            foreach ($stockEntry['godowns'] ?? [] as $ge) {
                $qty = (float) ($ge['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $godownOrder++;
                $totalQty += $qty;
                $godownEntryData[] = [
                    'entry_order' => $godownOrder,
                    'godown_id' => $ge['godown_id'],
                    'batch_no' => $ge['batch_no'] ?? null,
                    'mfg_date' => $ge['mfg_date'] ?? null,
                    'expiry_date' => $ge['expiry_date'] ?? null,
                    'actual_quantity' => $qty,
                    'movement_type' => MovementType::IN->value,
                    'remarks' => "Opening stock for {$fy->name}",
                ];
            }

            if ($totalQty <= 0) {
                continue;
            }

            $entryOrder++;
            $this->stockJournalEntryService->store([
                'stock_journal_id' => $stockJournal->id,
                'entry_order' => $entryOrder,
                'stock_item_id' => $itemId,
                'stock_unit_id' => $item->stock_unit_id,
                'actual_quantity' => $totalQty,
                'movement_type' => MovementType::IN->value,
                'stock_journal_godown_entries' => $godownEntryData,
            ]);
        }
    }

    // ──────────────────────────────────────────────
    //  Public API — Status
    // ──────────────────────────────────────────────

    public function getStatus(): array
    {
        $fy = $this->userFiscalYear->fiscalYear ?? $this->userFiscalYear->fiscal_year;
        $currentFyId = $fy->id;

        $existingOpening = Voucher::where('fiscal_year_id', $currentFyId)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'OPNJL'))
            ->with('voucher_type')
            ->first();

        return [
            'has_existing_opening' => (bool) $existingOpening,
            'opening_voucher_id' => $existingOpening?->id,
            'voucher_no' => $existingOpening?->voucher_no,
            'fiscal_year' => [
                'id' => $fy->id,
                'name' => $fy->name,
            ],
        ];
    }
}
