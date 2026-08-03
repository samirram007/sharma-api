<?php

namespace Modules\PhysicalStockCount\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Modules\PhysicalStockCount\Facades\PhysicalStockCountFacade;
use Modules\PhysicalStockCount\Requests\PhysicalStockCountRequest;
use Modules\PhysicalStockCount\Resources\PhysicalStockCountCollection;
use Modules\PhysicalStockCount\Resources\PhysicalStockCountResource;

class PhysicalStockCountController extends Controller
{
    use ApiResponseTrait;

    public function index(): SuccessCollection
    {
        $data = PhysicalStockCountFacade::getAll();

        return new PhysicalStockCountCollection($data);
    }

    public function show(int $id): SuccessResource
    {
        $data = PhysicalStockCountFacade::getById($id);

        return new PhysicalStockCountResource($data, 'Physical stock count retrieved successfully');
    }

    public function store(PhysicalStockCountRequest $request): SuccessResource
    {
        $data = PhysicalStockCountFacade::store($request->validated());

        return new PhysicalStockCountResource($data, 'Physical stock count created successfully');
    }

    public function update(PhysicalStockCountRequest $request, int $id): SuccessResource
    {
        $data = PhysicalStockCountFacade::update($request->validated(), $id);

        return new PhysicalStockCountResource($data, 'Physical stock count updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(PhysicalStockCountFacade::delete($id), 'Physical stock count');
    }

    /**
     * Auto-populate system quantities from stock journals
     */
    public function populateSystemQuantities(int $id): SuccessResource
    {
        $count = PhysicalStockCountFacade::populateSystemQuantities($id);

        return new SuccessResource($count, 'System quantities populated successfully');
    }

    /**
     * Verify a count sheet
     */
    public function verify(int $id): SuccessResource
    {
        $count = PhysicalStockCountFacade::verify($id);

        return new SuccessResource($count, 'Physical stock count verified successfully');
    }

    /**
     * Generate stock adjustment voucher from verified variances
     */
    public function generateAdjustment(int $id): SuccessResource
    {
        $count = PhysicalStockCountFacade::generateAdjustment($id);

        return new SuccessResource($count, 'Stock adjustment voucher generated successfully');
    }
}
