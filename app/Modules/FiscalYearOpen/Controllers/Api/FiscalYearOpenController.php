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
            // Optional user-edited opening stock quantities (item → godown → batch)
            'stock_items' => ['sometimes', 'array'],
            'stock_items.*.item_id' => ['required', 'integer', 'exists:stock_items,id'],
            'stock_items.*.godowns' => ['sometimes', 'array'],
            'stock_items.*.godowns.*.godown_id' => ['required', 'integer', 'exists:godowns,id'],
            'stock_items.*.godowns.*.quantity' => ['required', 'numeric', 'min:0'],
            'stock_items.*.godowns.*.batch_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stock_items.*.godowns.*.mfg_date' => ['sometimes', 'nullable', 'date'],
            'stock_items.*.godowns.*.expiry_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $result = FiscalYearOpenFacade::open(
            $request->input('new_fiscal_year_id'),
            $request->input('previous_fiscal_year_id'),
            $request->input('stock_items', [])
        );

        return new SuccessResource($result, $result['message']);
    }
}
