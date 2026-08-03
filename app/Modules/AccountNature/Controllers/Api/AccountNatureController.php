<?php

namespace Modules\AccountNature\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\AccountNature\Facades\AccountNatureFacade;
use Modules\AccountNature\Requests\AccountNatureRequest;
use Modules\AccountNature\Resources\AccountNatureCollection;
use Modules\AccountNature\Resources\AccountNatureResource;

class AccountNatureController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = AccountNatureFacade::getAll();

        return new AccountNatureCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = AccountNatureFacade::getById($id);

        return new AccountNatureResource($data, $message = 'AccountNature retrieved successfully');
    }

    public function store(AccountNatureRequest $request): SuccessResource
    {
        $data = AccountNatureFacade::store($request->validated());

        return new AccountNatureResource($data, $message = 'AccountNature create successfully');

    }

    public function update(AccountNatureRequest $request, int $id): SuccessResource
    {
        $data = AccountNatureFacade::update($request->validated(), $id);

        return new AccountNatureResource($data, $message = 'AccountNature updated successfully');

    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(AccountNatureFacade::delete($id), 'AccountNature');
    }
}
