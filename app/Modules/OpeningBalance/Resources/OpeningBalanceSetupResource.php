<?php

namespace Modules\OpeningBalance\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class OpeningBalanceSetupResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'currentFiscalYear' => [
                'id' => $this['current_fiscal_year']['id'],
                'name' => $this['current_fiscal_year']['name'],
                'startDate' => $this['current_fiscal_year']['start_date'],
                'endDate' => $this['current_fiscal_year']['end_date'],
            ],
            'previousFiscalYear' => $this['previous_fiscal_year'] ? [
                'id' => $this['previous_fiscal_year']['id'],
                'name' => $this['previous_fiscal_year']['name'],
                'isClosed' => $this['previous_fiscal_year']['is_closed'],
            ] : null,
            'hasExistingOpening' => $this['has_existing_opening'],
            'ledgers' => collect($this['ledgers'])->map(fn ($l) => [
                'ledgerId' => $l['ledger_id'],
                'ledgerName' => $l['ledger_name'],
                'ledgerCode' => $l['ledger_code'],
                'nature' => $l['nature'],
                'natureType' => $l['nature_type'],
                'prefilledBalance' => $l['prefilled_balance'],
            ]),
            'totalLedgers' => $this['total_ledgers'],
            'stockItems' => collect($this['stock_items'])->map(fn ($s) => [
                'itemId' => $s['item_id'],
                'itemName' => $s['item_name'],
                'unitCode' => $s['unit_code'],
                'unitName' => $s['unit_name'],
                'noOfDecimalPlaces' => $s['no_of_decimal_places'],
                'godowns' => collect($s['godowns'])->map(fn ($g) => [
                    'godownId' => $g['godown_id'],
                    'godownName' => $g['godown_name'],
                    'prefilledQuantity' => $g['prefilled_quantity'],
                ]),
            ]),
            'totalStockItems' => $this['total_stock_items'],
            'godowns' => collect($this['godowns'])->map(fn ($g) => [
                'id' => $g['id'],
                'name' => $g['name'],
                'code' => $g['code'],
            ]),

        ]);

    }
}
