<?php

namespace Modules\FiscalYearOpen\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Modules\FiscalYearOpen\Facades\FiscalYearOpenFacade;

class FiscalYearOpenController extends Controller
{
    use ApiResponseTrait;

    /**
     * Preview opening details before executing
     */
    public function preview(int $newFiscalYear, int $previousFiscalYear): SuccessResource
    {
        $data = FiscalYearOpenFacade::preview($newFiscalYear, $previousFiscalYear);

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

        $result = FiscalYearOpenFacade::open(
            $request->input('new_fiscal_year_id'),
            $request->input('previous_fiscal_year_id')
        );

        return new SuccessResource($result, $result['message']);
    }
}
