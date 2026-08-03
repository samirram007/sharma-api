<?php

namespace Modules\Company\Services;

use App\Support\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Address\Requests\AddressRequest;
use Modules\Company\Contracts\CompanyServiceInterface;
use Modules\Company\Facades\CompanyRepositoryFacade;
use Modules\Company\Models\Company;

class CompanyService extends BaseService implements CompanyServiceInterface
{
    protected string $modelClass = Company::class;

    protected array $defaultResource = ['company_type', 'address', 'fiscal_years', 'currency'];

    protected string $repositoryFacadeClass = CompanyRepositoryFacade::class;

    public function getAll(): LengthAwarePaginator
    {
        $perPage = request()->integer('per_page', 15);
        $status = request()->input('status');

        $query = CompanyRepositoryFacade::query()->with($this->defaultResource);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function store(array $data): Company
    {

        // transaction

        DB::beginTransaction();

        if (empty($data['mailing_name']) && ! empty($data['name'])) {
            $data['mailing_name'] = $data['name'];
        }
        $company = CompanyRepositoryFacade::create($data);

        if (! empty($data['address'])) {
            $data['address']['address_type'] = 'company';
            $data['address']['addressable_type'] = 'company';
            $data['address']['addressable_id'] = $company->id;

            $rules = (new AddressRequest)->rules();
            $validatedAddress = Validator::make($data['address'], $rules)->validate();

            $company->address()->create($validatedAddress);
        }

        DB::commit();

        return $company->load($this->defaultResource);
    }

    public function update(array $data, int $id): Company
    {
        $record = CompanyRepositoryFacade::find($id);
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
}
