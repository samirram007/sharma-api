<?php

namespace App\Modules\PhysicalStockCount\Services;

use App\Modules\PhysicalStockCount\Contracts\PhysicalStockCountServiceInterface;
use App\Modules\PhysicalStockCount\Models\PhysicalStockCount;
use App\Modules\PhysicalStockCount\Models\PhysicalStockCountItem;
use App\Modules\StockJournal\Contracts\StockJournalServiceInterface;
use App\Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use App\Modules\Voucher\Contracts\VoucherServiceInterface;
use App\Modules\Voucher\Models\Voucher;
use App\Modules\VoucherType\Models\VoucherType;
use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhysicalStockCountService implements PhysicalStockCountServiceInterface
{
    protected $resource = [
        'godown',
        'counted_by_user',
        'items.stock_item.stock_unit',
        'fiscal_year',
    ];

    public function __construct(
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
    ) {}

    public function getAll(): Collection
    {
        return PhysicalStockCount::with($this->resource)->get();
    }

    public function getById(int $id): ?PhysicalStockCount
    {
        return PhysicalStockCount::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): PhysicalStockCount
    {
        $data['counted_by'] = $data['counted_by'] ?? Auth::id();

        $count = PhysicalStockCount::create($data);

        // If items are included, store them
        if (!empty($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $count->items()->create($itemData);
            }
        }

        return $count->fresh($this->resource);
    }

    public function update(array $data, int $id): PhysicalStockCount
    {
        $record = PhysicalStockCount::findOrFail($id);

        if ($record->status !== 'draft') {
            throw new \Exception('Cannot update a verified or adjusted count sheet.');
        }

        $record->update($data);

        if (!empty($data['items'])) {
            // Delete removed items
            $existingIds = $record->items()->pluck('id')->toArray();
            $incomingIds = collect($data['items'])->pluck('id')->filter()->toArray();
            $toDelete = array_diff($existingIds, $incomingIds);
            if (!empty($toDelete)) {
                PhysicalStockCountItem::whereIn('id', $toDelete)->delete();
            }

            // Upsert items
            foreach ($data['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    PhysicalStockCountItem::find($itemData['id'])?->update($itemData);
                } else {
                    $record->items()->create($itemData);
                }
            }
        }

        return $record->fresh($this->resource);
    }

    public function delete(int $id): bool
    {
        $record = PhysicalStockCount::findOrFail($id);

        if ($record->status !== 'draft') {
            throw new \Exception('Cannot delete a verified or adjusted count sheet.');
        }

        return $record->delete();
    }

    public function populateSystemQuantities(int $countId): PhysicalStockCount
    {
        $count = PhysicalStockCount::with('godown', 'fiscal_year')->findOrFail($countId);

        if ($count->status !== 'draft') {
            throw new \Exception('Can only populate system quantities on draft count sheets.');
        }

        // Clear existing items to re-populate
        $count->items()->delete();

        // Query system stock quantities for this godown and fiscal year
        $stockData = DB::table('stock_journal_godown_entries')
            ->join('stock_journal_entries', 'stock_journal_godown_entries.stock_journal_entry_id', '=', 'stock_journal_entries.id')
            ->join('stock_journals', 'stock_journal_entries.stock_journal_id', '=', 'stock_journals.id')
            ->join('vouchers', 'stock_journals.id', '=', 'vouchers.stock_journal_id')
            ->where('stock_journal_godown_entries.godown_id', $count->godown_id)
            ->where('vouchers.fiscal_year_id', $count->fiscal_year_id)
            ->selectRaw('
                stock_journal_entries.stock_item_id,
                stock_journal_godown_entries.batch_no,
                stock_journal_godown_entries.mfg_date,
                stock_journal_godown_entries.expiry_date,
                stock_journal_godown_entries.serial_no,
                ROUND(SUM(CASE
                    WHEN stock_journal_godown_entries.movement_type = ? THEN stock_journal_godown_entries.actual_quantity
                    ELSE -stock_journal_godown_entries.actual_quantity
                END), 4) as net_quantity
            ', [MovementType::IN])
            ->groupBy(
                'stock_journal_entries.stock_item_id',
                'stock_journal_godown_entries.batch_no',
                'stock_journal_godown_entries.mfg_date',
                'stock_journal_godown_entries.expiry_date',
                'stock_journal_godown_entries.serial_no'
            )
            ->having('net_quantity', '!=', 0)
            ->get();

        $entryOrder = 0;
        foreach ($stockData as $data) {
            $entryOrder++;
            $count->items()->create([
                'stock_item_id' => $data->stock_item_id,
                'batch_no' => $data->batch_no,
                'mfg_date' => $data->mfg_date,
                'expiry_date' => $data->expiry_date,
                'serial_no' => $data->serial_no,
                'system_quantity' => $data->net_quantity,
                'physical_quantity' => 0,
                'entry_order' => $entryOrder,
            ]);
        }

        return $count->fresh($this->resource);
    }

    public function verify(int $countId): PhysicalStockCount
    {
        $count = PhysicalStockCount::with('items', 'fiscal_year', 'godown')->findOrFail($countId);

        if ($count->status !== 'draft') {
            throw new \Exception('Count sheet is already ' . $count->status . '.');
        }

        // Ensure all items have physical quantities entered
        $emptyItems = $count->items()->where('physical_quantity', 0)->count();
        if ($emptyItems > 0) {
            throw new \Exception("{$emptyItems} item(s) have zero physical quantity. Please enter counts for all items before verifying.");
        }

        $count->update(['status' => 'verified']);

        return $count->fresh($this->resource);
    }

    public function generateAdjustment(int $countId): PhysicalStockCount
    {
        $count = PhysicalStockCount::with('items', 'fiscal_year', 'godown')->findOrFail($countId);

        if ($count->status !== 'verified') {
            throw new \Exception('Count sheet must be verified before generating adjustment.');
        }

        $diffItems = $count->items()->where('difference', '!=', 0)->get();

        if ($diffItems->isEmpty()) {
            throw new \Exception('No variances found. No adjustment needed.');
        }

        DB::beginTransaction();
        try {
            $voucherType = VoucherType::where('code', 'SKADJ')->firstOrFail();

            // Create StockJournal for adjustment
            $stockJournal = $this->stockJournalService->store([
                'journal_no' => 'SKADJ-' . $count->id . '-' . now()->format('Ymd'),
                'journal_date' => now(),
                'type' => 'ADJUSTMENT',
                'remarks' => "Stock adjustment from physical count #{$count->id} at {$count->godown->name}",
            ]);

            foreach ($diffItems as $item) {
                $diff = (float) $item->difference;
                $movementType = $diff > 0 ? MovementType::OUT : MovementType::IN;
                $qty = abs($diff);

                $godownEntryData = [
                    [
                        'entry_order' => 1,
                        'godown_id' => $count->godown_id,
                        'actual_quantity' => $qty,
                        'movement_type' => $movementType,
                        'remarks' => "Physical count adjustment - system: {$item->system_quantity}, physical: {$item->physical_quantity}",
                    ],
                ];

                $this->stockJournalEntryService->store([
                    'stock_journal_id' => $stockJournal->id,
                    'entry_order' => $item->entry_order,
                    'stock_item_id' => $item->stock_item_id,
                    'actual_quantity' => $qty,
                    'movement_type' => $movementType,
                    'stock_journal_godown_entries' => $godownEntryData,
                ]);
            }

            // Create Voucher linking to adjustment StockJournal
            Voucher::create([
                'voucher_no' => 'SKADJ-' . $count->id . '-' . now()->format('Ymd'),
                'voucher_date' => now(),
                'voucher_type_id' => $voucherType->id,
                'fiscal_year_id' => $count->fiscal_year_id,
                'stock_journal_id' => $stockJournal->id,
                'remarks' => "Stock adjustment from physical count #{$count->id} at {$count->godown->name}",
                'status' => 'active',
                'is_effecting' => true,
                'effects_account' => false,
                'effects_stock' => true,
                'module' => 'system',
            ]);

            $count->update(['status' => 'adjusted']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhysicalStockAdjustment failed: ' . $e->getMessage(), [
                'count_id' => $countId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $count->fresh($this->resource);
    }
}
