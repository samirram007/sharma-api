<?php

namespace Modules\Godown\Services;

use App\Support\Services\BaseService;
use Illuminate\Support\Collection;
use Modules\Godown\Contracts\GodownServiceInterface;
use Modules\Godown\Models\Godown;

class GodownService extends BaseService implements GodownServiceInterface
{
    protected string $modelClass = Godown::class;

    protected array $defaultResource = ['parent', 'address'];

    public function store(array $data): Godown
    {
        if (empty($data['code'])) {

            $data['code'] = Godown::getUniqueCode();
            // dd($data);
        }

        $godown = Godown::create($data);
        // dd($data);
        if ($data['address']) {
            if (empty($data['address']['is_primary'])) {
                $data['address']['is_primary'] = false;
            }
            $data['address']['address_type'] = 'office';
            $data['address']['addressable_type'] = 'godown';
            $data['address']['addressable_id'] = $godown->id;

            $godown->address()->create($data['address']);
        }

        return $godown->load($this->defaultResource);
    }

    public function update(array $data, int $id): Godown
    {
        if (empty($data['code'])) {
            $data['code'] = $this->getUniqueCode();
        }
        $godown = Godown::findOrFail($id);

        $godown->update($data);

        if (isset($data['address'])) {
            if (empty($data['address']['is_primary'])) {
                $data['address']['is_primary'] = false;
            }
            $data['address']['address_type'] = 'office';
            $data['address']['addressable_type'] = 'godown';
            $data['address']['addressable_id'] = $godown->id;
            if (! empty($data['address']['id'])) {
                $address = $godown->address()->find($data['address']['id']);
                // dd($address);
                $address?->update($data['address']);
            } else {
                $godown->address()->create($data['address']);
            }
        }

        return $godown->fresh()->load($this->defaultResource);
    }

    public function getGodownItemStocks(int $item_id): Collection
    {
        return Godown::with([
            'stock_journal_godown_entries' => function ($q) use ($item_id) {
                $q->whereHas('stock_journal_entry', function ($q) use ($item_id) {
                    $q->where('stock_item_id', $item_id);
                })
                    ->select('id', 'godown_id', 'stock_journal_entry_id', 'actual_quantity'); // Important!
            },
            'stock_journal_godown_entries.stock_journal_entry:id,movement_type', // Join movement type
        ])
            ->where('storage_unit_type', 'GODOWN')
            ->get()
            ->map(function ($godown) use ($item_id) {

                $stockInHand = $godown->stock_journal_godown_entries->sum(function ($entry) {

                    $movement = strtoupper($entry->stock_journal_entry?->movement_type->value ?? 'OUT');

                    return $movement === 'IN'
                        ? $entry->actual_quantity
                        : -$entry->actual_quantity;
                });

                return [
                    'godownId' => $godown->id,
                    'storageUnitType' => $godown->storage_unit_type,
                    'itemId' => $item_id,
                    'stockInHand' => $stockInHand,
                ];
            });
    }

    public function getGodownItemBatches(int $item_id, int $godown_id): Collection
    {
        $godown = Godown::with([
            'stock_journal_godown_entries' => function ($q) use ($item_id) {

                // filter by stock item
                $q->whereHas('stock_journal_entry', function ($q) use ($item_id) {
                    $q->where('stock_item_id', $item_id);
                })
                    ->with([
                        'stock_journal_entry:id,stock_item_id,movement_type',
                    ]);

            },
        ])->find($godown_id);

        if (! $godown) {
            return collect([]);
        }

        $entries = $godown->stock_journal_godown_entries;

        // ⭐ GROUP BY BATCH (from Godown Entry)
        return $entries->groupBy('batch_no')->map(function ($batchEntries, $batchNo) {

            // Calculate IN - OUT quantity from godown entry
            $stock = $batchEntries->sum(function ($entry) {
                $movement = strtolower($entry->stock_journal_entry->movement_type->value);

                return $movement === 'in'
                    ? $entry->actual_quantity
                    : -$entry->actual_quantity;
            });

            $first = $batchEntries->first();

            return [
                'batchNo' => $batchNo,
                'mfgDate' => $first->mfg_date,
                'expiryDate' => $first->expiry_date,
                'stockInHand' => $stock,
            ];

        })->values();
    }

    public function getZones(): Collection
    {
        return Godown::where('storage_unit_type', 'ZONE')->get();
    }

    public function getZoneById(int $id): ?Godown
    {
        return Godown::where('storage_unit_type', 'ZONE')->findOrFail($id);
    }
}
