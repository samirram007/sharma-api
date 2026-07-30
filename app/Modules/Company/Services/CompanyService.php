<?php

namespace Modules\Company\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Address\Requests\AddressRequest;
use Modules\Company\Contracts\CompanyServiceInterface;
use Modules\Company\Models\Company;

class CompanyService implements CompanyServiceInterface
{
    protected $resource = ['company_type', 'address', 'fiscal_years', 'currency'];

    public function getAll(?string $status = null): Collection
    {
        $query = Company::with($this->resource);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getById(int $id): ?Company
    {

        return Company::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Company
    {

        // transaction

        DB::beginTransaction();

        if (empty($data['mailing_name']) && ! empty($data['name'])) {
            $data['mailing_name'] = $data['name'];
        }
        $company = Company::create($data);

        if (! empty($data['address'])) {
            $data['address']['address_type'] = 'company';
            $data['address']['addressable_type'] = 'company';
            $data['address']['addressable_id'] = $company->id;

            $rules = (new AddressRequest)->rules();
            $validatedAddress = Validator::make($data['address'], $rules)->validate();

            $company->address()->create($validatedAddress);
        }

        DB::commit();

        return $company->load($this->resource);
    }

    public function update(array $data, int $id): Company
    {
        $record = Company::findOrFail($id);
        $record->update($data);
        if (! empty($data['address'])) {
            $data['address']['is_primary'] = $data['address']['is_primary'] ?? false;

            $rules = (new AddressRequest)->rules();
            $validatedAddress = Validator::make($data['address'], $rules)->validate();

            //  dd($data['address']);
            if ($record->address) {
                $record->address->update($validatedAddress);
            } else {
                $data['address']['address_type'] = 'company';
                $data['address']['addressable_type'] = 'company';
                $data['address']['addressable_id'] = $record->id;
                $record->address()->create($validatedAddress);
            }
        }

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Company::findOrFail($id);

        return $record->delete();
    }
}
