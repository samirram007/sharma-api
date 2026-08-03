<?php

namespace Modules\FiscalYearOpen\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherType\Models\VoucherType;

class OpeningEntryReportController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get opening journal voucher details for a given fiscal year.
     * Returns the OPNJL voucher(s) with:
     * - Voucher entries (balance sheet ledgers carried forward)
     * - Stock journal entries with godown-level quantities
     */
    public function show(int $fiscalYearId): SuccessResource
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $openingJournalVoucherType = VoucherType::where('code', 'OPNJL')->first();

        if (! $openingJournalVoucherType) {
            return new SuccessResource([], 'No OpeningJournal voucher type found.');
        }

        $vouchers = Voucher::with([
            'voucher_entries.account_ledger.account_group.account_nature',
            'stock_journal.stock_journal_entries.stock_item',
            'stock_journal.stock_journal_entries.stock_unit',
            'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
            'voucher_type',
            'fiscal_year',
        ])
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('voucher_type_id', $openingJournalVoucherType->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [
            'fiscalYear' => [
                'id' => $fiscalYear->id,
                'name' => $fiscalYear->name,
                'startDate' => $fiscalYear->start_date?->toDateString(),
                'endDate' => $fiscalYear->end_date?->toDateString(),
            ],
            'vouchers' => $vouchers->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'voucherNo' => $voucher->voucher_no,
                    'voucherDate' => $voucher->voucher_date,
                    'remarks' => $voucher->remarks,
                    'createdAt' => $voucher->created_at,
                    'voucherEntries' => $voucher->voucher_entries->map(function ($entry) {
                        $nature = $entry->account_ledger?->account_group?->account_nature;

                        return [
                            'id' => $entry->id,
                            'entryOrder' => $entry->entry_order,
                            'accountLedgerId' => $entry->account_ledger_id,
                            'accountLedgerName' => $entry->account_ledger?->name,
                            'nature' => $nature?->name,
                            'natureCode' => $nature?->code,
                            'debit' => (float) ($entry->debit ?? 0),
                            'credit' => (float) ($entry->credit ?? 0),
                            'remarks' => $entry->remarks,
                        ];
                    }),
                    'totalDebit' => (float) $voucher->voucher_entries->sum(fn ($e) => $e->debit ?? 0),
                    'totalCredit' => (float) $voucher->voucher_entries->sum(fn ($e) => $e->credit ?? 0),
                    'stockJournal' => $voucher->stock_journal ? [
                        'id' => $voucher->stock_journal->id,
                        'journalNo' => $voucher->stock_journal->journal_no,
                        'journalDate' => $voucher->stock_journal->journal_date,
                        'type' => $voucher->stock_journal->type,
                        'entries' => $voucher->stock_journal->stock_journal_entries->map(function ($entry) {
                            return [
                                'id' => $entry->id,
                                'entryOrder' => $entry->entry_order,
                                'stockItemId' => $entry->stock_item_id,
                                'stockItemName' => $entry->stock_item?->name,
                                'stockUnitId' => $entry->stock_unit_id,
                                'stockUnitName' => $entry->stock_unit?->name,
                                'actualQuantity' => (float) ($entry->actual_quantity ?? 0),
                                'rate' => (float) ($entry->rate ?? 0),
                                'amount' => (float) ($entry->amount ?? 0),
                                'godownEntries' => $entry->stock_journal_godown_entries->map(function ($ge) {
                                    return [
                                        'id' => $ge->id,
                                        'entryOrder' => $ge->entry_order,
                                        'godownId' => $ge->godown_id,
                                        'godownName' => $ge->godown?->name,
                                        'batchNo' => $ge->batch_no,
                                        'mfgDate' => $ge->mfg_date?->toDateString(),
                                        'expiryDate' => $ge->expiry_date?->toDateString(),
                                        'actualQuantity' => (float) ($ge->actual_quantity ?? 0),
                                        'remarks' => $ge->remarks,
                                    ];
                                }),
                            ];
                        }),
                    ] : null,
                ];
            }),
            'totalVouchers' => $vouchers->count(),
        ];

        return new SuccessResource($data, 'Opening entry report retrieved successfully.');
    }

    /**
     * Get voucher entries grouped by ledger for a given fiscal year.
     */
    public function groupedByLedger(int $fiscalYearId): SuccessCollection
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $openingJournalVoucherType = VoucherType::where('code', 'OPNJL')->firstOrFail();

        $grouped = DB::table('voucher_entries')
            ->join('vouchers', 'voucher_entries.voucher_id', '=', 'vouchers.id')
            ->join('account_ledgers', 'voucher_entries.account_ledger_id', '=', 'account_ledgers.id')
            ->join('voucher_types', 'vouchers.voucher_type_id', '=', 'voucher_types.id')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->where('voucher_types.code', 'OPNJL')
            ->select(
                'account_ledgers.id',
                'account_ledgers.name',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw('SUM(voucher_entries.debit) as total_debit'),
                DB::raw('SUM(voucher_entries.credit) as total_credit'),
                DB::raw('SUM(voucher_entries.debit - voucher_entries.credit) as net_balance')
            )
            ->groupBy('account_ledgers.id', 'account_ledgers.name')
            ->orderByDesc('net_balance')
            ->get();

        $data = $grouped->map(fn ($row) => [
            'ledgerId' => $row->id,
            'ledgerName' => $row->name,
            'voucherCount' => (int) $row->voucher_count,
            'totalDebit' => (float) ($row->total_debit ?? 0),
            'totalCredit' => (float) ($row->total_credit ?? 0),
            'netBalance' => (float) ($row->net_balance ?? 0),
        ])->values();

        return new SuccessCollection($data, 'Opening entry report grouped by ledger retrieved successfully.');
    }
}
