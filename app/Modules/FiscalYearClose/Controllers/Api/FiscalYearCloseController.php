<?php

namespace Modules\FiscalYearClose\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Modules\FiscalYearClose\Contracts\FiscalYearCloseServiceInterface;

class FiscalYearCloseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected FiscalYearCloseServiceInterface $fiscalYearCloseService) {}

    /**
     * Preview closing summary before confirming close
     */
    public function preview(int $fiscalYear): SuccessResource
    {
        $data = $this->fiscalYearCloseService->preview($fiscalYear);

        return new SuccessResource($data, 'Closing preview retrieved successfully');
    }

    /**
     * Execute fiscal year closing
     */
    public function close(int $fiscalYear): SuccessResource
    {
        $result = $this->fiscalYearCloseService->close($fiscalYear);

        return new SuccessResource($result, $result['message']);
    }

    /**
     * Reopen a closed fiscal year
     */
    public function reopen(int $fiscalYear): SuccessResource
    {
        $result = $this->fiscalYearCloseService->reopen($fiscalYear);

        return new SuccessResource($result, $result['message']);
    }
}
