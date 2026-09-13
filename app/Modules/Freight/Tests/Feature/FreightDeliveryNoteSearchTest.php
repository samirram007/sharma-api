<?php

namespace Modules\Freight\Tests\Feature;

use App\Enums\MovementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;
use Modules\VoucherParty\Models\VoucherParty;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Feature tests for GET /api/freights/delivery_note search & amount filters.
 *
 * Regression coverage for the extended search: text matching now spans stock
 * item names/codes, dispatch-detail documents (dispatched through, order no,
 * receipt doc no, secondary destination) and party names; amount_min/amount_max
 * bound the dispatch-detail total_fare.
 */
class FreightDeliveryNoteSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Freight Search Tester',
            'email' => 'freight-search-tester@example.com',
            'password' => 'password',
        ]);
        $this->token = JWTAuth::fromUser($this->user);

        // FreightService hardcodes the delivery-note voucher type id (2001),
        // so the fixture must create the type with exactly that id.
        DB::table('voucher_types')->insert([
            'id' => 2001,
            'name' => 'Delivery Note '.Str::random(6),
            'code' => 'DLNT-'.Str::random(4),
            'voucher_category_id' => 1,
            'status' => 'active',
        ]);
    }

    /** One delivery-note voucher with a stock journal, item, party & fare. */
    private function createDeliveryNote(array $overrides = []): Voucher
    {
        $item = StockItem::create([
            'name' => $overrides['itemName'] ?? 'Cement OPC 53',
            'code' => $overrides['itemCode'] ?? 'CEM-'.Str::random(4),
            'status' => 'active',
        ]);

        $journal = StockJournal::create([
            'journal_no' => 'SJ-'.Str::random(6),
            'journal_date' => now(),
            'type' => 'stock',
        ]);

        $entry = StockJournalEntry::create([
            'stock_journal_id' => $journal->id,
            'entry_order' => 1,
            'stock_item_id' => $item->id,
            'actual_quantity' => 10,
            'billing_quantity' => 10,
            'movement_type' => MovementType::IN,
        ]);

        $godown = Godown::create([
            'name' => 'Search Godown '.Str::random(4),
            'code' => 'SG-'.Str::random(3),
            'status' => 'active',
            'storage_unit_type' => 'GODOWN',
        ]);

        StockJournalGodownEntry::create([
            'stock_journal_entry_id' => $entry->id,
            'entry_order' => 1,
            'godown_id' => $godown->id,
            'actual_quantity' => 10,
            'billing_quantity' => 10,
        ]);

        $voucher = Voucher::create([
            'voucher_no' => $overrides['voucherNo'] ?? 'DLNT-'.Str::random(5),
            'voucher_date' => now(),
            'voucher_type_id' => 2001,
            'stock_journal_id' => $journal->id,
            'fiscal_year_id' => $overrides['fiscalYearId'] ?? 1,
            'company_id' => $overrides['companyId'] ?? 1,
            'status' => 'active',
        ]);

        VoucherParty::create([
            'voucher_id' => $voucher->id,
            'name' => $overrides['partyName'] ?? 'DAS CEMENT AGENCY',
            'mailing_name' => $overrides['partyName'] ?? 'DAS CEMENT AGENCY',
        ]);

        VoucherDispatchDetail::create([
            'voucher_id' => $voucher->id,
            'carrier_name' => $overrides['carrierName'] ?? 'SHARMA TRANSPORT',
            'motor_vehicle_no' => $overrides['vehicleNo'] ?? 'JH01AB1234',
            'source' => $overrides['source'] ?? 'RANCHI',
            'destination' => $overrides['destination'] ?? 'DALTONGANJ',
            'bill_of_lading_no' => $overrides['lrNo'] ?? 'LR-777',
            'dispatched_through' => $overrides['dispatchedThrough'] ?? 'ROAD',
            'order_number' => $overrides['orderNumber'] ?? 'ORD-1001',
            'receipt_doc_no' => $overrides['receiptDocNo'] ?? 'RCPT-55',
            'freight_charges' => $overrides['freightCharges'] ?? 500,
            'total_fare' => $overrides['totalFare'] ?? 500,
        ]);

        return $voucher;
    }

    /** Give the signed-in user a fiscal-year period covering today. */
    private function attachFiscalYear(): void
    {
        DB::table('fiscal_years')->insert([
            'company_id' => 1,
            'name' => 'FY Test',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The freight list scopes vouchers to the user's custom reporting
        // period (user_fiscal_years.start_date/end_date) — it must cover the
        // vouchers' dates or every list query returns empty.
        DB::table('user_fiscal_years')->insert([
            'user_id' => $this->user->id,
            'fiscal_year_id' => 1,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'current_date' => now()->toDateString(),
        ]);
    }

    private function searchUrl(array $params = []): string
    {
        $query = http_build_query($params);

        return '/api/freights/delivery_note'.($query ? "?{$query}" : '');
    }

    public function test_search_matches_stock_item_name(): void
    {
        $this->attachFiscalYear();
        $this->createDeliveryNote(['itemName' => 'Ultra Cement Pro']);
        $this->createDeliveryNote(['itemName' => 'Steel Rod', 'voucherNo' => 'DLNT-STEEL']);

        $response = $this->withToken($this->token)
            ->getJson($this->searchUrl(['search' => 'ultra cement', 'freight_status' => 'all']))
            ->assertOk();

        $voucherNos = collect($response->json('data'))->pluck('voucherNo')->all();
        expect($voucherNos)->not->toBeEmpty();

        foreach ($voucherNos as $voucherNo) {
            expect($voucherNo)->not->toBe('DLNT-STEEL');
        }
    }

    public function test_search_matches_dispatch_documents_and_party(): void
    {
        $this->attachFiscalYear();
        $this->createDeliveryNote(['orderNumber' => 'ORD-SPECIAL-42']);

        $this->withToken($this->token)
            ->getJson($this->searchUrl(['search' => 'ORD-SPECIAL-42', 'freight_status' => 'all']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($this->token)
            ->getJson($this->searchUrl(['search' => 'DAS CEMENT', 'freight_status' => 'all']))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_amount_range_bounds_fare(): void
    {
        $this->attachFiscalYear();
        $this->createDeliveryNote(['totalFare' => 100]);
        $this->createDeliveryNote(['totalFare' => 5000]);

        $inside = collect($this->withToken($this->token)
            ->getJson($this->searchUrl(['amount_min' => 50, 'amount_max' => 1000, 'freight_status' => 'all']))
            ->json('data'))->count();

        $outside = collect($this->withToken($this->token)
            ->getJson($this->searchUrl(['amount_min' => 2000, 'freight_status' => 'all']))
            ->json('data'))->count();

        expect($inside)->toBe(1)
            ->and($outside)->toBe(1);
    }

    public function test_zero_fare_voucher_matches_unbounded_max_but_not_min(): void
    {
        $this->attachFiscalYear();
        $this->createDeliveryNote(['totalFare' => 0, 'freightCharges' => 0]);

        // No amount filter — visible in the pending list.
        $this->withToken($this->token)
            ->getJson($this->searchUrl())
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // An explicit minimum excludes zero-fare vouchers.
        $this->withToken($this->token)
            ->getJson($this->searchUrl(['amount_min' => 1]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
