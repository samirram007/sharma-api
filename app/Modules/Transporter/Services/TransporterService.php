<?php

namespace Modules\Transporter\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\Transporter\Contracts\TransporterServiceInterface;
use Modules\Transporter\Models\Transporter;

class TransporterService implements TransporterServiceInterface
{
    protected $resource = ['account_ledger', 'address'];

    public function getAll(): Collection
    {
        return Transporter::with($this->resource)->get();
    }

    public function getById(int $id): ?Transporter
    {
        return Transporter::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Transporter
    {
        $transporter = Transporter::create($data);

        if ($data['address']) {

            $data['address']['address_type'] = 'office';
            $data['address']['addressable_type'] = 'transporter';
            $data['address']['addressable_id'] = $transporter->id;
            // dump($data['address']);
            $transporter->address()->create($data['address']);
            // dd($data['address']);
        }
        if ($data['account_group_id']) {

            $data['account_ledger']['name'] = $transporter->name;
            $data['account_ledger']['code'] = $transporter->name;
            $data['account_ledger']['account_group_id'] = $data['account_group_id'];
            $data['account_ledger']['ledgerable_type'] = 'transporter';
            $data['account_ledger']['ledgerable_id'] = $transporter->id;
            // dump($data['address']);
            $transporter->account_ledger()->create($data['account_ledger']);
            // dd($data['address']);
        }

        // 'ledgerable_id',
        // 'ledgerable_type'
        return $transporter->load($this->resource);
    }

    public function update(array $data, int $id): Transporter
    {
        $transporter = Transporter::findOrFail($id);
        $previousName = $transporter->name;
        $transporter->update($data);

        if (isset($data['address'])) {

            $data['address']['address_type'] = 'office';
            $data['address']['addressable_type'] = 'transporter';
            $data['address']['addressable_id'] = $transporter->id;
            if (! empty($data['address']['id'])) {
                $address = $transporter->address()->find($data['address']['id']);
                $address?->update($data['address']);
            } else {
                $transporter->address()->create($data['address']);
            }
        }
        $isDirty = $previousName != $data['name'] ? true : false;

        if (isset($data['account_group_id'])) {
            $accountLedger = AccountLedger::where('ledgerable_id', $transporter->id)->first();

            if ($isDirty) {
                $data['account_ledger']['name'] = $this->verifyUniqueLedgerName($transporter->name, $transporter->id);
                $data['account_ledger']['code'] = $data['account_ledger']['name'];
            }
            $data['account_ledger']['account_group_id'] = $data['account_group_id'];
            $data['account_ledger']['ledgerable_type'] = 'transporter';
            $data['account_ledger']['ledgerable_id'] = $transporter->id;
            if ($accountLedger) {
                $accountLedger->update($data['account_ledger']);
            } else {

                $transporter->account_ledger()->create($data['account_ledger']);
            }

        }

        return $transporter->fresh()->load($this->resource);
    }

    public function delete(int $id): bool
    {
        $record = Transporter::findOrFail($id);

        return $record->delete();
    }
}
