<?php

namespace Modules\Voucher\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\FiscalYear\Facades\FiscalYearRepositoryFacade;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournal\Requests\StockJournalRequest;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\Voucher\Contracts\VoucherServiceInterface;
use Modules\Voucher\Facades\VoucherRepositoryFacade;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Requests\VoucherDispatchDetailRequest;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherEntry\Requests\VoucherEntryRequest;
use Modules\VoucherNo\Models\VoucherNo;
use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;
use Modules\VoucherParty\Requests\VoucherPartyRequest;
use Modules\VoucherReference\Contracts\VoucherReferenceServiceInterface;
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

    protected UserFiscalYearServiceInterface $userFiscalYearService;

    protected StockJournalServiceInterface $stockJournalService;

    protected VoucherEntryServiceInterface $voucherEntryService;

    protected VoucherDispatchDetailServiceInterface $voucherDispatchDetailService;

    protected VoucherPartyServiceInterface $voucherPartyService;

    public function __construct(
        UserFiscalYearServiceInterface $userFiscalYearService,
        StockJournalServiceInterface $stockJournalService,
        VoucherEntryServiceInterface $voucherEntryService,
        VoucherDispatchDetailServiceInterface $voucherDispatchDetailService,
        VoucherPartyServiceInterface $voucherPartyService
    ) {
        $this->userFiscalYearService = $userFiscalYearService;
        $this->stockJournalService = $stockJournalService;
        $this->voucherEntryService = $voucherEntryService;
        $this->voucherDispatchDetailService = $voucherDispatchDetailService;
        $this->voucherPartyService = $voucherPartyService;
    }

    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────

    public function getAll(): Collection
    {
        $vouchers = VoucherRepositoryFacade::with($this->defaultResource)
            ->sortBy('created_at', 'desc')
            ->getAllFiltered();

        return $vouchers->map(fn (Voucher $voucher) => $this->attachLedgerInfo($voucher));
    }

    public function getByModule(string $module): Collection
    {
        $vouchers = VoucherRepositoryFacade::with($this->defaultResource)
            ->filter(['module' => $module])
            ->sortBy('created_at', 'desc')
            ->getAllFiltered();

        return $vouchers->map(fn (Voucher $voucher) => $this->attachLedgerInfo($voucher));
    }

    public function getByVoucherType(int $voucherTypeId): Collection
    {
        $vouchers = VoucherRepositoryFacade::with($this->defaultResource)
            ->filter(['voucher_type_id' => $voucherTypeId])
            ->sortBy('created_at', 'desc')
            ->getAllFiltered();

        return $vouchers->map(fn (Voucher $voucher) => $this->attachLedgerInfo($voucher));
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
            $userFiscalYear = $this->userFiscalYearService->getByUserId(auth()->guard()->user()->id);
            $fiscalYearId = $data['fiscal_year_id'] ?? $userFiscalYear->fiscal_year->id;

            $this->validateFiscalYear($fiscalYearId);

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
                $this->voucherEntryService->store($validatedEntry);
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
            $this->voucherDispatchDetailService->store($validatedDispatch);
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
            $this->voucherPartyService->store($validatedParty);
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
            app(VoucherReferenceServiceInterface::class)->store($validatedReference);
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
                    $createdStockJournal = $this->stockJournalService->store($validatedStockJournal);
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
                        $this->voucherEntryService->delete($voucherEntry['id']);
                    } else {
                        $this->voucherEntryService->update($validatedEntry, $voucherEntry['id']);
                    }
                } else {
                    $this->voucherEntryService->store($validatedEntry);
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
                $this->voucherDispatchDetailService->store($validatedDispatch);
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
                $this->voucherPartyService->update($validatedParty, $data['party']['id']);
            } else {
                $this->voucherPartyService->store($validatedParty);
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
