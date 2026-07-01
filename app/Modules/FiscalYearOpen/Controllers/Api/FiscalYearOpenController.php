<?php

namespace Modules\FiscalYearOpen\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use Modules\FiscalYearOpen\Contracts\FiscalYearOpenServiceInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class FiscalYearOpenController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected FiscalYearOpenServiceInterface $fiscalYearOpenService)
    {
    }

    /**
     * Preview opening details before executing
     */
    public function preview(int $newFiscalYear, int $previousFiscalYear): SuccessResource
    {
        $data = $this->fiscalYearOpenService->preview($newFiscalYear, $previousFiscalYear);
        return new SuccessResource($data, 'Opening preview retrieved successfully');
    }

    /**
     * Execute fiscal year opening
     */
    public function open(Request $request): SuccessResource
    {
        $request->validate([
            'new_fiscal_year_id' => 'required|exists:fiscal_years,id',
            'previous_fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        $result = $this->fiscalYearOpenService->open(
            $request->input('new_fiscal_year_id'),
            $request->input('previous_fiscal_year_id')
        );
        return new SuccessResource($result, $result['message']);
    }
}
