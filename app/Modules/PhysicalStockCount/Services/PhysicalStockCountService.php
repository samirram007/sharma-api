<?php

namespace Modules\PhysicalStockCount\Services;

use App\Enums\MovementType;
use App\Support\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PhysicalStockCount\Contracts\PhysicalStockCountServiceInterface;
use Modules\PhysicalStockCount\Models\PhysicalStockCount;
use Modules\PhysicalStockCount\Models\PhysicalStockCountItem;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherType\Models\VoucherType;

class PhysicalStockCountService extends BaseService implements PhysicalStockCountServiceInterface
{
    protected string $modelClass = PhysicalStockCount::class;

    protected array $defaultResource = [
        'godown',
        'counted_by_user',
        'items.stock_item.stock_unit',
        'fiscal_year',
    ];

    public function __construct(
        protected StockJournalServiceInterface $stockJournalService,
        protected StockJournalEntryServiceInterface $stockJournalEntryService,
    ) {}

    public function store(array $data): PhysicalStockCount
    {
        $data['counted_by'] = $data['counted_by'] ?? Auth::id();

        $count = PhysicalStockCount::create($data);

        // If items are included, store them
        if (! empty($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $count->items()->create($itemData);
            }
        }

        return $count->fresh($this->defaultResource);
    }

    public function update(array $data, int $id): PhysicalStockCount
    {
        $record = PhysicalStockCount::findOrFail($id);

        if ($record->status !== 'draft') {
            throw new \Exception('Cannot update a verified or adjusted count sheet.');
        }

        $record->update($data);

        if (! empty($data['items'])) {
            // Delete removed items
            $existingIds = $record->items()->pluck('id')->toArray();
            $incomingIds = collect($data['items'])->pluck('id')->filter()->toArray();
            $toDelete = array_diff($existingIds, $incomingIds);
            if (! empty($toDelete)) {
                PhysicalStockCountItem::whereIn('id', $toDelete)->delete();
            }

            // Upsert items
            foreach ($data['items'] as $itemData) {
                if (! empty($itemData['id'])) {
                    PhysicalStockCountItem::find($itemData['id'])?->update($itemData);
                } else {
                    $record->items()->create($itemData);
                }
            }
        }

        return $record->fresh($this->defaultResource);
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

        // Query system stock quantities for this godown and fiscal year. The rate
        // comes from the most recent stock movement for that item/batch (falling
        // back to the item's standard cost) so count rows are pre-valued.
        $stockData = DB::table('stock_journal_godown_entries')
            ->join('stock_journal_entries', 'stock_journal_godown_entries.stock_journal_entry_id', '=', 'stock_journal_entries.id')
            ->join('stock_journals', 'stock_journal_entries.stock_journal_id', '=', 'stock_journals.id')
            ->join('vouchers', 'stock_journals.id', '=', 'vouchers.stock_journal_id')
            ->leftJoin('stock_items', 'stock_items.id', '=', 'stock_journal_entries.stock_item_id')
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
                END), 4) as net_quantity,
                COALESCE(
                    (
                        SELECT sjge2.rate
                        FROM stock_journal_godown_entries AS sjge2
                        JOIN stock_journal_entries AS sje2 ON sje2.id = sjge2.stock_journal_entry_id
                        JOIN stock_journals AS sj2 ON sj2.id = sje2.stock_journal_id
                        JOIN vouchers AS v2 ON v2.stock_journal_id = sj2.id
                        WHERE v2.fiscal_year_id = ?
                            AND sjge2.godown_id = stock_journal_godown_entries.godown_id
                            AND sje2.stock_item_id = stock_journal_entries.stock_item_id
                            AND COALESCE(sjge2.batch_no, \'\') = COALESCE(stock_journal_godown_entries.batch_no, \'\')
                            AND COALESCE(sjge2.mfg_date, \'\') = COALESCE(stock_journal_godown_entries.mfg_date, \'\')
                            AND COALESCE(sjge2.expiry_date, \'\') = COALESCE(stock_journal_godown_entries.expiry_date, \'\')
                        ORDER BY sj2.journal_date DESC, sj2.id DESC
                        LIMIT 1
                    ),
                    -- one stock_items row per item, so MAX() is safe and keeps
                    -- the query compatible with ONLY_FULL_GROUP_BY
                    MAX(stock_items.standard_cost)
                ) as rate
            ', [MovementType::IN->value, $count->fiscal_year_id])
            // The correlated rate subquery references the outer godown_id, so
            // it must be grouped too for ONLY_FULL_GROUP_BY (MariaDB/MySQL 5.7+).
            // The query filters by a single godown, so this never merges rows.
            ->groupBy(
                'stock_journal_godown_entries.godown_id',
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
                'rate' => (float) $data->rate,
                'entry_order' => $entryOrder,
            ]);
        }

        return $count->fresh($this->defaultResource);
    }

    public function verify(int $countId): PhysicalStockCount
    {
        $count = PhysicalStockCount::with('items', 'fiscal_year', 'godown')->findOrFail($countId);

        if ($count->status !== 'draft') {
            throw new \Exception('Count sheet is already '.$count->status.'.');
        }

        // A physical quantity of 0 is a valid count (item fully missing = a
        // complete loss), so no minimum-quantity check is enforced here.

        $count->update(['status' => 'verified']);

        return $count->fresh($this->defaultResource);
    }

    public function generateAdjustment(int $countId): PhysicalStockCount
    {
        $count = PhysicalStockCount::with('items', 'fiscal_year', 'godown')->findOrFail($countId);

        if ($count->status !== 'verified') {
            throw new \Exception('Count sheet must be verified before generating adjustment.');
        }

        $diffItems = $count->items()->with('stock_item')->where('difference', '!=', 0)->get();

        if ($diffItems->isEmpty()) {
            throw new \Exception('No variances found. No adjustment needed.');
        }

        DB::beginTransaction();
        try {
            $voucherType = VoucherType::where('code', 'SKADJ')->firstOrFail();

            // Summarize the variances so the journal/voucher narrations describe
            // the count sheet properly (how many loss vs surplus lines).
            $lossCount = $diffItems->filter(fn ($i) => (float) $i->difference > 0)->count();
            $surplusCount = $diffItems->filter(fn ($i) => (float) $i->difference < 0)->count();
            $netDiff = round((float) $diffItems->sum('difference'), 4);
            $summary = sprintf(
                'Stock adjustment from physical count #%s at %s — %s loss line(s), %s surplus line(s), net diff %s',
                $count->id,
                $count->godown->name,
                $lossCount,
                $surplusCount,
                $netDiff
            );

            // Create StockJournal for adjustment
            $stockJournal = $this->stockJournalService->store([
                'journal_no' => 'SKADJ-'.$count->id.'-'.now()->format('Ymd'),
                'journal_date' => now(),
                'type' => 'ADJUSTMENT',
                'remarks' => $summary,
            ]);

            foreach ($diffItems as $item) {
                $diff = (float) $item->difference;
                // difference = system - physical; a positive difference means stock is
                // missing / LOSS (OUT), a negative difference means surplus (IN).
                $movementType = $diff > 0 ? MovementType::OUT->value : MovementType::IN->value;
                $qty = abs($diff);
                // Rates are auto-filled on populate/picker select; fall back to
                // the item's standard cost when no rate was recorded.
                $rate = (float) $item->rate;
                if ($rate <= 0) {
                    $rate = (float) ($item->stock_item?->standard_cost ?? 0);
                }

                // Describe each entry line: item + batch + book vs physical.
                $nature = $diff > 0 ? 'Stock loss' : 'Stock surplus';
                $itemName = $item->stock_item?->name ?? "Item #{$item->stock_item_id}";
                $batchPart = $item->batch_no ? " (batch: {$item->batch_no})" : '';

                $godownEntryData = [
                    [
                        'entry_order' => 1,
                        'godown_id' => $count->godown_id,
                        'actual_quantity' => $qty,
                        'billing_quantity' => $qty,
                        'rate' => $rate,
                        'amount' => round($qty * $rate, 2),
                        'movement_type' => $movementType,
                        // Carry the batch info through so the adjustment entry
                        // keeps the batch of the counted stock.
                        'batch_no' => $item->batch_no,
                        'mfg_date' => $item->mfg_date?->format('Y-m-d'),
                        'expiry_date' => $item->expiry_date?->format('Y-m-d'),
                        'serial_no' => $item->serial_no,
                        'remarks' => "{$nature} - {$itemName}{$batchPart} - book: {$item->system_quantity}, physical: {$item->physical_quantity}",
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
                'voucher_no' => 'SKADJ-'.$count->id.'-'.now()->format('Ymd'),
                'voucher_date' => now(),
                'voucher_type_id' => $voucherType->id,
                'fiscal_year_id' => $count->fiscal_year_id,
                'stock_journal_id' => $stockJournal->id,
                'remarks' => $summary,
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
            Log::error('PhysicalStockAdjustment failed: '.$e->getMessage(), [
                'count_id' => $countId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $count->fresh($this->defaultResource);
    }
}
