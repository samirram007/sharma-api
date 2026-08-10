<?php

namespace Modules\Voucher\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\FiscalYear\Facades\FiscalYearRepositoryFacade;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\Godown\Resources\GodownResource;
use Modules\StockItem\Models\StockItem;
use Modules\StockItem\Resources\StockItemResource;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournal\Facades\StockJournalFacade;
use Modules\StockJournal\Requests\StockJournalRequest;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\StockUnit\Resources\StockUnitResource;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Facades\UserFiscalYearFacade;
use Modules\Voucher\Contracts\VoucherServiceInterface;
use Modules\Voucher\Facades\VoucherRepositoryFacade;
use Modules\Voucher\Models\Voucher;
use Modules\Voucher\Resources\VoucherResource;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Facades\VoucherDispatchDetailFacade;
use Modules\VoucherDispatchDetail\Requests\VoucherDispatchDetailRequest;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherEntry\Facades\VoucherEntryFacade;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherEntry\Requests\VoucherEntryRequest;
use Modules\VoucherNo\Models\VoucherNo;
use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;
use Modules\VoucherParty\Facades\VoucherPartyFacade;
use Modules\VoucherParty\Requests\VoucherPartyRequest;
use Modules\VoucherReference\Contracts\VoucherReferenceServiceInterface;
use Modules\VoucherReference\Facades\VoucherReferenceFacade;
use Modules\VoucherReference\Models\VoucherReference;
use Modules\VoucherReference\Requests\VoucherReferenceRequest;
use Modules\VoucherType\Models\VoucherType;

class VoucherService extends BaseService implements VoucherServiceInterface
{
    protected string $modelClass = Voucher::class;

    protected array $defaultResource = [
        'voucher_type',
        'voucher_entries.account_ledger',
        'stock_journal.stock_journal_entries.rate_unit',
        'stock_journal.stock_journal_entries.stock_item.stock_unit',
        'stock_journal.stock_journal_entries.stock_item.alternate_stock_unit',
        'stock_journal.stock_journal_entries.alternate_unit',
        'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
        'voucher_party.state',
        'voucher_party.country',
        'voucher_dispatch_detail',
        'company',
        'fiscal_year',
    ];

    /**
     * Leaner eager-load set for the list endpoints (index). The full detail
     * graph above is only needed when editing a single voucher (getById),
     * where it is hydrated once — loading it for every voucher in a list
     * response is what exhausts PHP memory on larger datasets.
     */
    protected array $listResource = [
        'voucher_type',
        'voucher_entries.account_ledger',
        'voucher_party.state',
        'voucher_party.country',
        'voucher_dispatch_detail',
        'company',
        'fiscal_year',
        'stock_journal',
    ];

    protected UserFiscalYearServiceInterface $userFiscalYearService;

    protected StockJournalServiceInterface $stockJournalService;

    protected StockSummaryServiceInterface $stockSummaryService;

    protected VoucherEntryServiceInterface $voucherEntryService;

    protected VoucherDispatchDetailServiceInterface $voucherDispatchDetailService;

    protected VoucherPartyServiceInterface $voucherPartyService;

    public function __construct(
        UserFiscalYearServiceInterface $userFiscalYearService,
        StockJournalServiceInterface $stockJournalService,
        StockSummaryServiceInterface $stockSummaryService,
        VoucherEntryServiceInterface $voucherEntryService,
        VoucherDispatchDetailServiceInterface $voucherDispatchDetailService,
        VoucherPartyServiceInterface $voucherPartyService
    ) {
        $this->userFiscalYearService = $userFiscalYearService;
        $this->stockJournalService = $stockJournalService;
        $this->stockSummaryService = $stockSummaryService;
        $this->voucherEntryService = $voucherEntryService;
        $this->voucherDispatchDetailService = $voucherDispatchDetailService;
        $this->voucherPartyService = $voucherPartyService;
    }

    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────

    /**
     * Resolve the fiscal year the current user is working in, so voucher lists
     * are isolated per fiscal year. Returns null when the user has no assigned
     * fiscal year (empty list result) unless $required is true.
     */
    protected function resolveUserFiscalYearId(bool $required = false): ?int
    {
        $userFiscalYear = auth()->guard()->user()?->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            if ($required) {
                throw new \Exception('UserFiscalYear not set for the user.');
            }

            return null;
        }

        return (int) $userFiscalYear->fiscal_year_id;
    }

    /**
     * All voucher entries are isolated per fiscal year: the list only returns
     * vouchers of the user's assigned fiscal year (the same rule the reports
     * already follow). Pass an explicit $fiscalYearId to override.
     */
    public function getAll(?int $fiscalYearId = null): Collection
    {
        $fiscalYearId ??= $this->resolveUserFiscalYearId();

        $vouchers = VoucherRepositoryFacade::with($this->listResource)
            ->cache(false)
            ->filter(['fiscal_year_id' => $fiscalYearId])
            ->sortBy('created_at', 'desc')
            ->getAllFiltered();

        return $this->attachListInfo($vouchers);
    }

    /**
     * Module-scoped voucher list — also isolated per fiscal year (e.g. the
     * Freight list only shows the user's current FY). Pass an explicit
     * $fiscalYearId to override.
     */
    public function getByModule(string $module, ?int $fiscalYearId = null): Collection
    {
        $fiscalYearId ??= $this->resolveUserFiscalYearId();

        $vouchers = VoucherRepositoryFacade::with($this->listResource)
            ->cache(false)
            ->filter(['module' => $module, 'fiscal_year_id' => $fiscalYearId])
            ->sortBy('created_at', 'desc')
            ->getAllFiltered();

        return $this->attachListInfo($vouchers);
    }

    /**
     * Voucher-type-scoped list — isolated per fiscal year too. Defaults to
     * the user's assigned fiscal year; pass an explicit id to override (e.g.
     * viewing a previous year's vouchers).
     */
    public function getByVoucherType(int $voucherTypeId, ?int $fiscalYearId = null): Collection
    {
        $fiscalYearId ??= $this->resolveUserFiscalYearId();

        $vouchers = VoucherRepositoryFacade::with($this->listResource)
            ->cache(false)
            ->filter(['voucher_type_id' => $voucherTypeId, 'fiscal_year_id' => $fiscalYearId])
            ->sortBy('created_at', 'desc')
            ->getAllFiltered();

        return $this->attachListInfo($vouchers);
    }

    public function getById(int $id): ?Voucher
    {
        $extraResource = ['voucher_references.reference_voucher.voucher_dispatch_detail', 'referenced_by'];
        $this->defaultResource = array_merge($this->defaultResource, $extraResource);
        $voucher = VoucherRepositoryFacade::with($this->defaultResource)->find($id);

        return $this->attachLedgerInfo($voucher);
    }

    /**
     * Store a new voucher through the processing pipeline.
     *
     * The entire pipeline runs inside a single DB transaction with pessimistic
     * locking on the VoucherNo row to guarantee unique voucher numbers even
     * under concurrent requests. Laravel auto-retries up to 5 times on deadlock.
     */
    public function store(array $data): Voucher
    {
        return DB::transaction(function () use ($data) {
            $userFiscalYear = UserFiscalYearFacade::getByUserId(auth()->guard()->user()->id);
            $fiscalYearId = $data['fiscal_year_id'] ?? $userFiscalYear->fiscal_year->id;

            $this->validateFiscalYear($fiscalYearId);

            // Opening Stock (OPNSK) vouchers are always dated on the first day
            // of the fiscal year — the entry date cannot be changed by the user.
            $data = $this->enforceOpeningStockRulesStep($data, $fiscalYearId);

            // Only ONE opening stock voucher is allowed per fiscal year.
            $this->enforceSingleOpeningStockVoucherPerFy($fiscalYearId);

            // Pipeline Step 1: Process Stock Journal
            $data = $this->processStockJournalStep($data);

            // Pipeline Step 2: Generate Voucher No (with pessimistic row lock)
            $companyId = $data['company_id'] ?? $userFiscalYear->fiscal_year->company_id;
            $data = $this->generateVoucherNoStep($data, $fiscalYearId, $companyId);

            // Pipeline Step 3: Create Voucher
            $voucher = $this->createVoucherStep($data, $fiscalYearId);

            // Pipeline Step 4: Process Voucher Entries
            $this->processVoucherEntriesStep($data, $voucher);

            // Pipeline Step 5: Process Dispatch Detail
            $this->processDispatchDetailStep($data, $voucher);

            // Pipeline Step 6: Process Party
            $this->processPartyStep($data, $voucher);

            // Pipeline Step 7: Process Reference
            $this->processVoucherReferenceStep($data, $voucher);

            return $voucher;
        }, 5);
    }

    /**
     * Update an existing voucher through the processing pipeline.
     *
     * Uses pessimistic locking on both the voucher row (findWithLock)
     * and the VoucherNo row to prevent concurrent update conflicts.
     * Laravel auto-retries up to 5 times on deadlock.
     */
    public function update(array $data, int $id): Voucher
    {
        return DB::transaction(function () use ($data, $id) {
            // Pessimistic lock: prevent concurrent updates to the same voucher
            $voucher = VoucherRepositoryFacade::findWithLock($id);

            if ($voucher->locked) {
                throw new \Exception('This voucher is locked and cannot be updated.');
            }

            $fiscalYearId = $data['fiscal_year_id'] ?? $voucher->fiscal_year_id;
            $this->validateFiscalYear($fiscalYearId);

            // Opening Stock (OPNSK) vouchers are always dated on the first day
            // of the fiscal year — the entry date cannot be changed by the user.
            $data = $this->enforceOpeningStockRulesStep($data, $fiscalYearId, $voucher);

            // Pipeline Step 1: Process Stock Journal (update path)
            $data = $this->processStockJournalUpdateStep($data, $voucher);

            // Pipeline Step 2: Generate Voucher No if needed (with pessimistic row lock)
            $companyId = $data['company_id'] ?? 1;
            $data = $this->generateVoucherNoStep($data, $fiscalYearId, $companyId);

            // Pipeline Step 3: Update Voucher
            $voucher = $this->updateVoucherStep($data, $voucher);

            // Pipeline Step 4: Process Voucher Entries (update path)
            $this->processVoucherEntriesUpdateStep($data, $voucher);

            // Pipeline Step 5: Process Dispatch Detail (update path)
            $this->processDispatchDetailUpdateStep($data, $voucher);

            // Pipeline Step 6: Process Party (update path)
            $this->processPartyUpdateStep($data, $voucher);

            // Pipeline Step 7: Process Reference
            $this->processVoucherReferenceStep($data, $voucher);

            return $voucher->fresh();
        }, 5);
    }

    public function delete(int $id): bool
    {
        return VoucherRepositoryFacade::delete($id);
    }

    // ──────────────────────────────────────────────
    //  Opening Stock — Previous Year Closing
    // ──────────────────────────────────────────────

    /**
     * Fetch the previous fiscal year's closing stock with all godown and batch
     * details, so the Opening Stock screen can pre-fill its entries from the
     * prior year's year-end stock freeze.
     *
     * When the previous fiscal year has no frozen CLSSK closing journal (it was
     * never closed, or was closed without stock), this falls back to the previous
     * year's RUNNING balance — computed live from stock movements using the same
     * net-quantity / weighted-average logic the fiscal year close uses — and
     * returns it as a synthetic closing voucher (source: 'running') so opening
     * stock can still be pre-filled instead of erroring out.
     */
    public function getPreviousYearClosingStock(): array
    {
        $userFiscalYear = UserFiscalYearFacade::getByUserId(auth()->guard()->user()->id);
        $fiscalYear = $userFiscalYear->fiscal_year;

        // The previous fiscal year is the one that ends before this one starts
        // (scoped to the same company so multi-company installs never pull
        // another company's closing stock).
        $prevFy = FiscalYear::where('end_date', '<', $fiscalYear->start_date)
            ->where('company_id', $fiscalYear->company_id)
            ->orderBy('end_date', 'desc')
            ->first();

        $source = null;
        $closingVoucherNo = null;
        $closingDate = null;
        $closingVoucher = null;

        // Preferred source: the frozen CLSSK closing journal from the closed
        // previous fiscal year.
        if ($prevFy && $prevFy->closed_at) {
            $closingStockVoucher = Voucher::where('fiscal_year_id', $prevFy->id)
                ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSSK'))
                ->with([
                    'voucher_type',
                    'stock_journal.stock_journal_entries.rate_unit',
                    'stock_journal.stock_journal_entries.stock_item.stock_unit',
                    'stock_journal.stock_journal_entries.stock_item.alternate_stock_unit',
                    'stock_journal.stock_journal_entries.alternate_unit',
                    'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
                ])
                ->first();

            if ($closingStockVoucher) {
                $source = 'closing_journal';
                $closingVoucherNo = $closingStockVoucher->voucher_no;
                $closingDate = $closingStockVoucher->voucher_date?->toDateString();
                $closingVoucher = new VoucherResource($this->attachLedgerInfo($closingStockVoucher));
            }
        }

        // Fallback: no frozen closing journal → compute the previous FY's
        // running balance live from its stock movements.
        if (! $closingVoucher && $prevFy) {
            $source = 'running';
            $runningItems = $this->stockSummaryService
                ->runningClosingStockItems($prevFy->id, $prevFy->end_date);

            if (! empty($runningItems)) {
                $closingVoucher = $this->buildSyntheticClosingVoucher($runningItems, $prevFy);
                $closingDate = $prevFy->end_date?->toDateString();
            }
        }

        return [
            'previousFiscalYear' => $prevFy ? [
                'id' => $prevFy->id,
                'name' => $prevFy->name,
                'isClosed' => (bool) $prevFy->closed_at,
            ] : null,
            'source' => $source,
            'closingVoucherNo' => $closingVoucherNo,
            'closingDate' => $closingDate,
            'closingVoucher' => $closingVoucher,
        ];
    }

    /**
     * Build a synthetic closing-voucher payload (same camelCase shape as
     * VoucherResource → stockJournal → stockJournalEntries →
     * stockJournalGodownEntries) from running-balance items, so the Opening
     * Stock screen can pre-fill its entries even when the previous fiscal year
     * has no frozen CLSSK closing journal.
     *
     * Quantities come from the running balance; rates use the weighted average
     * inward rate already attached by buildRunningClosingStock(). Nested
     * stockItem / stockUnit / godown objects are serialized with the same
     * resources the standard voucher payload uses.
     */
    protected function buildSyntheticClosingVoucher(array $runningItems, FiscalYear $prevFy): array
    {
        $itemIds = array_column($runningItems, 'item_id');
        $godownIds = array_values(array_unique(array_merge(
            ...array_map(
                fn ($item) => array_column($item['godown_details'] ?? [], 'godown_id'),
                $runningItems
            )
        )));

        // alternate_stock_unit is loaded too — the grid's RateBox/QuantityBox
        // read it to build conversion factors, so the serialized stockItem must
        // match the CLSSK path (which loads it via the voucher eager loads).
        $stockItems = StockItem::with('stock_unit', 'alternate_stock_unit')->whereIn('id', $itemIds)->get()->keyBy('id');
        $godowns = Godown::whereIn('id', $godownIds)->get()->keyBy('id');

        $entries = [];
        foreach ($runningItems as $item) {
            $stockItem = $stockItems->get($item['item_id']);
            if (! $stockItem) {
                continue;
            }

            $godownEntries = [];
            foreach ($item['godown_details'] ?? [] as $godownDetail) {
                $batches = $godownDetail['batch_details'] ?? [];

                // Every godown entry carries its own batch info — expand one
                // godown entry per batch so batch numbers / mfg / expiry dates
                // survive the pre-fill.
                if (empty($batches)) {
                    $qty = (float) ($godownDetail['closing_quantity'] ?? 0);
                    $batches = [[
                        'quantity' => $qty,
                        'amount' => $godownDetail['closing_amount'] ?? 0,
                        'rate' => $qty > 0
                            ? round((float) $godownDetail['closing_amount'] / $qty, 4)
                            : null,
                    ]];
                }

                foreach ($batches as $batch) {
                    $qty = (float) ($batch['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $godown = $godowns->get($godownDetail['godown_id']);

                    $godownEntries[] = [
                        'id' => null,
                        'stockJournalEntryId' => null,
                        'godownId' => (int) $godownDetail['godown_id'],
                        'batchNo' => $batch['batch_no'] ?? null,
                        'mfgDate' => $batch['mfg_date'] ?? null,
                        'expiryDate' => $batch['expiry_date'] ?? null,
                        'serialNo' => null,
                        'actualQuantity' => $qty,
                        'billingQuantity' => $qty,
                        'rate' => isset($batch['rate']) && $batch['rate'] !== null
                            ? round((float) $batch['rate'], 4)
                            : null,
                        'amount' => round((float) ($batch['amount'] ?? 0), 2),
                        'movementType' => 'in',
                        'remarks' => null,
                        'godown' => $godown ? GodownResource::make($godown)->resolve() : null,
                    ];
                }
            }

            if (empty($godownEntries)) {
                continue;
            }

            $totalQty = array_sum(array_column($godownEntries, 'actualQuantity'));
            $totalAmount = array_sum(array_column($godownEntries, 'amount'));

            $entries[] = [
                'id' => null,
                'stockJournalId' => null,
                'stockItemId' => (int) $stockItem->id,
                'stockUnitId' => $stockItem->stock_unit_id,
                // Backend StockJournalEntryRequest marks these required — mirror
                // the full field set the CLSSK serialization provides so saving
                // a pre-filled opening stock passes validation.
                'unitRatio' => 0,
                'itemCost' => 0,
                'actualQuantity' => $totalQty,
                'billingQuantity' => $totalQty,
                'rate' => $totalQty > 0 ? round($totalAmount / $totalQty, 4) : null,
                'rateUnitId' => $stockItem->stock_unit_id,
                'rateUnitRatio' => 1,
                'discountPercentage' => 0,
                'discount' => 0,
                'amount' => round($totalAmount, 2),
                'movementType' => 'in',
                'stockItem' => StockItemResource::make($stockItem)->resolve(),
                'stockUnit' => $stockItem->stock_unit
                    ? StockUnitResource::make($stockItem->stock_unit)->resolve()
                    : null,
                'stockJournalGodownEntries' => $godownEntries,
            ];
        }

        return [
            'id' => null,
            'voucherNo' => null,
            'voucherDate' => $prevFy->end_date?->toDateString(),
            'voucherTypeId' => null,
            'module' => 'opening_stock',
            'fiscalYearId' => $prevFy->id,
            'stockJournalId' => null,
            'stockJournal' => [
                'id' => null,
                'journalNo' => null,
                'journalDate' => $prevFy->end_date?->toDateString(),
                'voucherId' => null,
                'type' => 'in',
                'remarks' => 'Running balance carried forward from '.$prevFy->name.' (no closing journal found)',
                'stockJournalEntries' => $entries,
            ],
            'voucherEntries' => [],
        ];
    }

    /**
     * Resolve the "Opening Stock" (OPNSK) voucher type at runtime using the
     * same code-based lookup the enforcement step uses.
     *
     * The OPNSK id is NOT stable across databases — legacy installs seeded it
     * as 9010, fresh installs as 10004 — so the frontend must never hardcode
     * it. It fetches this endpoint and uses the returned id for the voucher
     * list filter and store/update payloads.
     */
    public function getOpeningStockVoucherType(): array
    {
        $openingStockType = VoucherType::where('code', 'OPNSK')->first();

        if (! $openingStockType) {
            return [];
        }

        return [
            'id' => (int) $openingStockType->id,
            'code' => (string) $openingStockType->code,
            'name' => (string) $openingStockType->name,
        ];
    }

    /**
     * Opening Stock (OPNSK) vouchers are always dated on the first day of the
     * fiscal year (the entry date cannot be changed by the user) and must
     * always carry the canonical OPNSK voucher type id. Both are forced on
     * every store/update.
     *
     * Detection uses BOTH the resolved type id and the module signal, so the
     * rules hold even if a client sends a wrong (but existing) type id.
     */
    protected function enforceOpeningStockRulesStep(array $data, int $fiscalYearId, ?Voucher $voucher = null): array
    {
        $openingStockType = VoucherType::where('code', 'OPNSK')->first();

        $isOpeningStock = $openingStockType && (
            (int) ($data['voucher_type_id'] ?? 0) === (int) $openingStockType->id
            || ($voucher && (int) $voucher->voucher_type_id === (int) $openingStockType->id)
            || ($data['module'] ?? null) === 'opening_stock'
        );

        if ($isOpeningStock) {
            // Stamp the canonical OPNSK id so the saved voucher always carries
            // the type that actually exists in this database.
            $data['voucher_type_id'] = (int) $openingStockType->id;

            $fiscalYear = FiscalYear::find($fiscalYearId);
            if ($fiscalYear && $fiscalYear->start_date) {
                $data['voucher_date'] = $fiscalYear->start_date->toDateString();
            }
        }

        return $data;
    }

    /**
     * Only ONE opening stock (OPNSK) voucher may exist per fiscal year. This
     * is the server-side guarantee — the frontend also blocks duplicates, but
     * a second voucher created through any other client must be rejected too.
     *
     * Called on the STORE path (before the voucher is created); the update
     * path is exempt because it targets the one existing voucher.
     */
    protected function enforceSingleOpeningStockVoucherPerFy(int $fiscalYearId): void
    {
        $openingStockType = VoucherType::where('code', 'OPNSK')->first();
        if (! $openingStockType) {
            return;
        }

        $existing = Voucher::where('voucher_type_id', $openingStockType->id)
            ->where('fiscal_year_id', $fiscalYearId)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'voucher_type_id' => 'Opening stock already exists for this fiscal year. Only one opening stock voucher is allowed per fiscal year.',
            ]);
        }
    }

    // ──────────────────────────────────────────────
    //  Pipeline Steps — Store
    // ──────────────────────────────────────────────

    /**
     * Step 1 (store): Create a stock journal if stock_journal data is provided.
     */
    protected function processStockJournalStep(array $data): array
    {
        if (isset($data['stock_journal']) && ! empty($data['stock_journal'])) {
            $stockJournal = $data['stock_journal'];
            $rules = (new StockJournalRequest)->rules();
            $validatedStockJournal = Validator::make($stockJournal, $rules)->validate();
            if (! empty($validatedStockJournal)) {
                $createdStockJournal = $this->stockJournalService->store($validatedStockJournal);
                $data['stock_journal_id'] = $createdStockJournal->id ?? null;
            }
        }

        return $data;
    }

    /**
     * Step 2: Generate a voucher number if one is not provided.
     *
     * Uses lockForUpdate() on the VoucherNo row to prevent concurrent
     * transactions from generating the same number (pessimistic lock).
     * This is the primary contention point — two requests arriving at
     * the same time will queue up behind this lock.
     */
    protected function generateVoucherNoStep(array $data, int $fiscalYearId, int $companyId): array
    {
        if (! isset($data['voucher_no']) || empty($data['voucher_no']) || $data['voucher_no'] === 'new') {
            $voucherTypeId = $data['voucher_type_id'];
            $branchId = $data['branch_id'] ?? null;

            // Lock the VoucherNo row so concurrent requests queue up here
            $voucherNoRecord = VoucherNo::where('voucher_type_id', $voucherTypeId)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->lockForUpdate()
                ->first();

            if ($voucherNoRecord) {
                $voucherNoRecord->current_no += 1;
                $voucherNoRecord->save();
            } else {
                $voucherType = VoucherType::find($voucherTypeId);
                $prefix = $voucherType ? substr($voucherType->code, 0, 4).'-' : 'VCH-';
                $voucherNoRecord = VoucherNo::create([
                    'prefix' => $prefix,
                    'voucher_type_id' => $voucherTypeId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId ?? null,
                    'fiscal_year_id' => $fiscalYearId,
                    'starting_no' => 1,
                    'current_no' => 1,
                ]);
            }

            $data['voucher_no'] = $voucherNoRecord->prefix.$voucherNoRecord->current_no;
        }

        return $data;
    }

    /**
     * Step 3: Create the voucher record from sanitized data.
     */
    protected function createVoucherStep(array $data, int $fiscalYearId): Voucher
    {
        $sanitizedData = ['fiscal_year_id' => $fiscalYearId];
        $voucherModel = new Voucher;
        foreach ($data as $key => $value) {
            if (in_array($key, $voucherModel->getFillable(), true)) {
                $sanitizedData[$key] = $value;
            }
        }

        return VoucherRepositoryFacade::create($sanitizedData);
    }

    /**
     * Step 4 (store): Create voucher entries.
     */
    protected function processVoucherEntriesStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['voucher_entries'])) {
            foreach ($data['voucher_entries'] as $voucherEntry) {
                $voucherEntry['voucher_id'] = $voucher->id;
                $rules = (new VoucherEntryRequest)->rules();
                $validatedEntry = Validator::make($voucherEntry, $rules)->validate();
                VoucherEntryFacade::store($validatedEntry);
            }
        }
    }

    /**
     * Step 5 (store): Create dispatch detail.
     */
    protected function processDispatchDetailStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['voucher_dispatch_detail'])) {
            $data['voucher_dispatch_detail']['voucher_id'] = $voucher->id;
            $rules = (new VoucherDispatchDetailRequest)->rules();
            $validatedDispatch = Validator::make($data['voucher_dispatch_detail'], $rules)->validate();

            VoucherDispatchDetailFacade::store($validatedDispatch);
        }
    }

    /**
     * Step 6 (store): Create party.
     */
    protected function processPartyStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['party'])) {
            $data['party']['voucher_id'] = $voucher->id;
            $rules = (new VoucherPartyRequest)->rules();
            $validatedParty = Validator::make($data['party'], $rules)->validate();
            VoucherPartyFacade::store($validatedParty);
        }
    }

    /**
     * Step 7: Create voucher reference (shared by store and update).
     */
    protected function processVoucherReferenceStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['voucher_reference'])) {
            $data['voucher_reference']['voucher_id'] = $voucher->id;
            $rules = (new VoucherReferenceRequest)->rules();
            $validatedReference = Validator::make($data['voucher_reference'], $rules)->validate();
            VoucherReferenceFacade::store($validatedReference);
            // app(VoucherReferenceServiceInterface::class)->store($validatedReference);
        }
    }

    // ──────────────────────────────────────────────
    //  Pipeline Steps — Update
    // ──────────────────────────────────────────────

    /**
     * Step 1 (update): Update or create stock journal.
     */
    protected function processStockJournalUpdateStep(array $data, Voucher $voucher): array
    {
        if (isset($data['stock_journal']) && ! empty($data['stock_journal'])) {
            if ($voucher->stock_journal_id) {
                if (isset($data['stock_journal']['id']) && $data['stock_journal']['id'] == $voucher->stock_journal_id) {
                    $this->stockJournalService->update($data['stock_journal'], $data['stock_journal']['id']);
                } else {
                    throw new \Exception(
                        'Stock Journal is already assigned to this voucher. Cannot assign a different stock journal.'
                    );
                }
            } else {
                $stockJournal = $data['stock_journal'];
                $rules = (new StockJournalRequest)->rules();
                $validatedStockJournal = Validator::make($stockJournal, $rules)->validate();
                if (! empty($validatedStockJournal)) {
                    $createdStockJournal = StockJournalFacade::store($validatedStockJournal);
                    $data['stock_journal_id'] = $createdStockJournal->id ?? null;
                }
            }

            unset($data['stock_journal']);
        }

        return $data;
    }

    /**
     * Step 3 (update): Update the voucher record.
     */
    protected function updateVoucherStep(array $data, Voucher $voucher): Voucher
    {
        $sanitizedData = ['fiscal_year_id' => $voucher->fiscal_year_id];
        foreach ($data as $key => $value) {
            if (in_array($key, $voucher->getFillable(), true)) {
                $sanitizedData[$key] = $value;
            }
        }

        // Only update if any value differs
        $voucher->fill($sanitizedData);
        if ($voucher->isDirty()) {
            VoucherRepositoryFacade::update($sanitizedData, $voucher->id);
        }

        return VoucherRepositoryFacade::with($this->defaultResource)->find($voucher->id);
    }

    /**
     * Step 4 (update): Create / update / delete voucher entries.
     */
    protected function processVoucherEntriesUpdateStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['voucher_entries'])) {
            foreach ($data['voucher_entries'] as $voucherEntry) {
                $voucherEntry['voucher_id'] = $voucher->id;
                $rules = (new VoucherEntryRequest)->rules();
                $validatedEntry = Validator::make($voucherEntry, $rules)->validate();

                if ($voucherEntry['id'] ?? false) {
                    if (isset($validatedEntry['is_deleted']) && $validatedEntry['is_deleted']) {
                        VoucherEntryFacade::delete($voucherEntry['id']);
                    } else {
                        VoucherEntryFacade::update($validatedEntry, $voucherEntry['id']);
                    }
                } else {
                    VoucherEntryFacade::store($validatedEntry);
                }
            }
        }
    }

    /**
     * Step 5 (update): Create or update dispatch detail.
     */
    protected function processDispatchDetailUpdateStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['voucher_dispatch_detail'])) {
            $data['voucher_dispatch_detail']['voucher_id'] = $voucher->id;
            $rules = (new VoucherDispatchDetailRequest)->rules();
            $validatedDispatch = Validator::make($data['voucher_dispatch_detail'], $rules)->validate();

            if ($data['voucher_dispatch_detail']['id'] ?? false) {
                $this->voucherDispatchDetailService->update(
                    $validatedDispatch,
                    $data['voucher_dispatch_detail']['id']
                );
            } else {
                VoucherDispatchDetailFacade::store($validatedDispatch);
            }
        }
    }

    /**
     * Step 6 (update): Create or update party.
     */
    protected function processPartyUpdateStep(array $data, Voucher $voucher): void
    {
        if (! empty($data['party'])) {
            $data['party']['voucher_id'] = $voucher->id;
            $rules = (new VoucherPartyRequest)->rules();
            $validatedParty = Validator::make($data['party'], $rules)->validate();

            if ($data['party']['id'] ?? false) {
                VoucherPartyFacade::update($validatedParty, $data['party']['id']);
            } else {
                VoucherPartyFacade::store($validatedParty);
            }
        }
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Validate that the fiscal year exists and is active.
     */
    protected function validateFiscalYear(int $fiscalYearId): void
    {
        $fiscalYear = FiscalYearRepositoryFacade::find($fiscalYearId);
        if (! $fiscalYear) {
            throw new \Exception('Fiscal Year not found.');
        }

        if (! $fiscalYear->isActive()) {
            throw new \Exception("Fiscal Year '{$fiscalYear->name}' is inactive. Voucher operations are not allowed.");
        }
    }

    /**
     * Attach the computed list fields (party ledger, transaction ledger,
     * amount, payment status) to a whole collection using a handful of
     * grouped queries, instead of the per-voucher N+1 (4 ledger sums + 2-4
     * payment queries each) that the single-voucher paths run.
     */
    protected function attachListInfo(Collection $vouchers): Collection
    {
        if ($vouchers->isEmpty()) {
            return $vouchers;
        }

        $voucherIds = $vouchers->pluck('id')->all();

        // 1) Which vouchers reference payment / freight-payment vouchers.
        $paymentRefs = VoucherReference::whereIn('ref_voucher_id', $voucherIds)
            ->whereIn('type', ['payment', 'freight_payment'])
            ->get(['ref_voucher_id', 'voucher_id']);

        $paymentIdsByVoucher = $paymentRefs->groupBy('ref_voucher_id');
        $paymentVoucherIds = $paymentRefs->pluck('voucher_id')->unique()->values()->all();

        // 2) Total paid amount per payment voucher — plain rows summed in PHP so
        // the query stays portable (GREATEST() is not supported on the SQLite
        // test database). Mirrors getAmountAttribute(): max(debit, credit) per row.
        $paidByPaymentVoucher = [];
        foreach (VoucherEntry::whereIn('voucher_id', $paymentVoucherIds)->get(['voucher_id', 'debit', 'credit']) as $entry) {
            $paidByPaymentVoucher[$entry->voucher_id] = ($paidByPaymentVoucher[$entry->voucher_id] ?? 0)
                + (float) ($entry->debit ?: $entry->credit ?: 0);
        }

        // 3) Current balance for every distinct ledger referenced by the collection.
        $ledgerIds = $vouchers->flatMap(
            fn (Voucher $voucher) => $voucher->voucher_entries->pluck('account_ledger_id')
        )->unique()->values()->all();

        $ledgerBalances = VoucherEntry::whereIn('account_ledger_id', $ledgerIds)
            ->selectRaw('account_ledger_id, SUM(debit) - SUM(credit) AS balance')
            ->groupBy('account_ledger_id')
            ->pluck('balance', 'account_ledger_id');

        $purchaseGroupId = 40001;
        $salesGroupId = 50001;
        $stockGroupId = 10009;

        foreach ($vouchers as $voucher) {
            $entries = $voucher->voucher_entries;

            // Detect party ledger (Customer / Supplier / Distributor / Transporter)
            $partyEntry = $entries->first(fn ($entry) => in_array(
                $entry->account_ledger?->ledgerable_type,
                ['customer', 'supplier', 'distributor', 'transporter']
            ));

            // Detect transaction ledger using account_group_id
            $transactionEntry = $entries->first(fn ($entry) => in_array(
                $entry->account_ledger?->account_group_id,
                [$purchaseGroupId, $salesGroupId, $stockGroupId]
            ));

            $partyLedger = $partyEntry?->account_ledger;
            $transactionLedger = $transactionEntry?->account_ledger;

            $voucher->setRelation(
                'party_ledger',
                $partyLedger
                    ? array_merge(
                        $partyLedger->only(['id', 'name', 'code', 'ledgerable_type', 'ledgerable_id']),
                        ['current_balance' => (float) ($ledgerBalances[$partyLedger->id] ?? 0)]
                    )
                    : null
            );

            $voucher->setRelation(
                'transaction_ledger',
                $transactionLedger
                    ? array_merge(
                        $transactionLedger->only(['id', 'name', 'code', 'account_group_id']),
                        ['current_balance' => (float) ($ledgerBalances[$transactionLedger->id] ?? 0)]
                    )
                    : null
            );

            // Attach voucher amount (total debit or credit) as a relation so the
            // getAmountAttribute() accessor short-circuits even when list mode
            // empties the voucher_entries relation before serialization.
            $voucher->setRelation('amount', $entries->sum(fn ($entry) => $entry->debit ?: $entry->credit ?: 0));

            // Payment status — mirrors Voucher::getPaymentStatusAttribute()
            $totalPaid = $paymentIdsByVoucher->get($voucher->id)
                ?->sum(fn ($ref) => (float) ($paidByPaymentVoucher[$ref->voucher_id] ?? 0)) ?? 0;

            $voucher->setRelation('payment_status', match (true) {
                $totalPaid >= (float) $voucher->amount => 'paid',
                $totalPaid > 0 => 'partially_paid',
                default => 'unpaid',
            });

            // List rows never render voucherEntries (edit screens fetch the
            // full graph via getById) — flag the voucher so VoucherResource
            // skips serializing them (the single biggest cost in the payload).
            $voucher->isListMode = true;
        }

        return $vouchers;
    }

    /**
     * Attach computed ledger info (party ledger, transaction ledger, amount)
     * to the voucher for API responses.
     */
    public function attachLedgerInfo(Voucher $voucher): Voucher
    {
        // Detect party ledger (Customer / Supplier / Distributor / Transporter)
        $partyEntry = $voucher->voucher_entries
            ->first(fn ($entry) => in_array(
                $entry->account_ledger->ledgerable_type,
                ['customer', 'supplier', 'distributor', 'transporter']
            ));

        // Detect transaction ledger using account_group_id
        $purchaseGroupId = 40001;
        $salesGroupId = 50001;
        $stockGroupId = 10009;

        $transactionEntry = $voucher->voucher_entries
            ->first(fn ($entry) => in_array(
                $entry->account_ledger->account_group_id,
                [$purchaseGroupId, $salesGroupId, $stockGroupId]
            ));

        // Calculate current balance for party ledger
        $partyCurrentBalance = $partyEntry?->account_ledger
            ? $partyEntry->account_ledger->voucher_entries()->sum('debit')
              - $partyEntry->account_ledger->voucher_entries()->sum('credit')
            : 0;

        // Calculate current balance for transaction ledger
        $transactionCurrentBalance = $transactionEntry?->account_ledger
            ? $transactionEntry->account_ledger->voucher_entries()->sum('debit')
              - $transactionEntry->account_ledger->voucher_entries()->sum('credit')
            : 0;

        // Attach full ledger objects with current balance
        $voucher->setRelation(
            'party_ledger',
            $partyEntry?->account_ledger
                ? array_merge(
                    $partyEntry->account_ledger->only(['id', 'name', 'code', 'ledgerable_type', 'ledgerable_id']),
                    ['current_balance' => $partyCurrentBalance]
                )
                : null
        );

        $voucher->setRelation(
            'transaction_ledger',
            $transactionEntry?->account_ledger
                ? array_merge(
                    $transactionEntry->account_ledger->only(['id', 'name', 'code', 'account_group_id']),
                    ['current_balance' => $transactionCurrentBalance]
                )
                : null
        );

        // Attach voucher amount (total debit or credit)
        $voucher->amount = $voucher->voucher_entries->sum(
            fn ($entry) => $entry->debit ?: $entry->credit ?: 0
        );

        return $voucher;
    }
}
