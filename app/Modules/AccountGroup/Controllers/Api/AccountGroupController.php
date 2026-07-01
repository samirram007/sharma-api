<?php

namespace Modules\AccountGroup\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
// use Modules\AccountGroup\Contracts\AccountGroupServiceInterface;
use Modules\AccountGroup\Facades\AccountGroup;
use Modules\AccountGroup\Facades\AccountGroupFacade;
use Modules\AccountGroup\Resources\AccountGroupCollection;
use Modules\AccountGroup\Resources\AccountGroupResource;

use Modules\AccountGroup\Requests\AccountGroupRequest;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AccountGroupController extends Controller
{
    use ApiResponseTrait;

    protected $service;

    public function __construct(protected AccountGroupFacade $accountGroupFacade)
    {
        $this->service = $accountGroupFacade;
    }

    public function index(): SuccessCollection
    {

        $data = AccountGroupFacade::getAll();

        return new AccountGroupCollection($data);
    }
    public function show(int $id): ?SuccessResource
    {
        $data = $this->service->getById($id);
        // dd($data);
        return new AccountGroupResource($data, $message = 'AccountGroup retrieved successfully');
    }

    public function store(AccountGroupRequest $request): SuccessResource
    {
        $data = $this->service->store($request->validated());
        return
            new AccountGroupResource(
                $data,
                $message = 'AccountGroup created successfully',
            );
    }

    public function update(AccountGroupRequest $request, int $id): SuccessResource
    {

        $data = $this->service->update($request->validated(), $id);
        return new AccountGroupResource($data, $message = 'AccountGroup updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {

        $result = $this->service->delete($id);
        return new JsonResponse([
            'status' => $result,
            'code' => 204,
            'message' => $result ? 'AccountGroup deleted successfully' : 'AccountGroup not found',
        ]);
    }






    public function current_liability_groups(): SuccessCollection
    {

        $data = $this->service->getCurrentLiabilityGroups();

        return new AccountGroupCollection($data);
    }
}
