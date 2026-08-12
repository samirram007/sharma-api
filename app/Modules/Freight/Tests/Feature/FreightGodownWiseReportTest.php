<?php

namespace Modules\Freight\Tests\Feature;

use Modules\FiscalYear\Models\FiscalYear;
use Modules\Freight\Contracts\FreightServiceInterface;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\StockJournalGodownEntry\Models\StockJournalGodownEntry;
use Modules\StockUnit\Models\StockUnit;
use Modules\User\Models\User;
use Modules\UserFiscalYear\Models\UserFiscalYear;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;
use Modules\VoucherReference\Models\VoucherReference;

/**
 * Create a delivery-note voucher (2001) → stock journal → entry → godown entry.
 */
function createDeliveryNote(
    string $voucherNo,
    string $voucherDate,
    StockItem $item,
    StockUnit $unit,
    Godown $godown,
    int $fiscalYearId,
    float $quantity,
    float $rate,
    string $movementType = 'in',
): Voucher {
    $amount = round($quantity * $rate, 2);

    $voucher = Voucher::create([
        'voucher_no' => $voucherNo,
        'voucher_date' => $voucherDate,
        'voucher_type_id' => 2001, // delivery note
        'fiscal_year_id' => $fiscalYearId,
        'company_id' => 1,
        'module' => 'delivery_note',
        'status' => 'active',
    ]);

    $journal = StockJournal::create([
        'journal_no' => $voucherNo,
        'journal_date' => $voucherDate,
        'type' => 'SALE',
        'remarks' => 'Delivery note movement',
    ]);
    $voucher->update(['stock_journal_id' => $journal->id]);

    $entry = StockJournalEntry::create([
        'stock_journal_id' => $journal->id,
        'entry_order' => 1,
        'stock_item_id' => $item->id,
        'stock_unit_id' => $unit->id,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => $rate,
        'rate_unit_id' => $unit->id,
        'amount' => $amount,
        'movement_type' => $movementType,
    ]);

    StockJournalGodownEntry::create([
        'stock_journal_entry_id' => $entry->id,
        'entry_order' => 1,
        'godown_id' => $godown->id,
        'actual_quantity' => $quantity,
        'billing_quantity' => $quantity,
        'rate' => $rate,
        'amount' => $amount,
        'movement_type' => $movementType,
    ]);

    return $voucher;
}

/**
 * Link a freight (sales, 1006) voucher to a delivery note via voucher_references.
 */
function createFreightVoucher(Voucher $deliveryNote, int $fiscalYearId, string $voucherNo, string $voucherDate): Voucher
{
    $freight = Voucher::create([
        'voucher_no' => $voucherNo,
        'voucher_date' => $voucherDate,
        'voucher_type_id' => 1006, // sales voucher used for freight bills
        'fiscal_year_id' => $fiscalYearId,
        'company_id' => 1,
        'module' => 'freight',
        'status' => 'active',
    ]);

    VoucherReference::create([
        'voucher_id' => $freight->id,
        'ref_voucher_id' => $deliveryNote->id,
        'type' => 'freight',
    ]);

    return $freight;
}

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Freight Report Test User',
        'email' => 'freight-report-test@example.com',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);

    $this->fiscalYear = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
        'status' => 'active',
        'company_id' => 1,
    ]);

    // User's fiscal year mapping — reporting period defaults to the full FY.
    $this->userFiscalYear = UserFiscalYear::create([
        'user_id' => $this->user->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'start_date' => '2025-04-01',
        'end_date' => '2026-03-31',
    ]);

    $this->unit = StockUnit::create(['name' => 'Kilogram', 'code' => 'KG']);
    $this->godownA = Godown::create(['name' => 'Godown A', 'code' => 'GA']);
    $this->godownB = Godown::create(['name' => 'Godown B', 'code' => 'GB']);
    $this->item = StockItem::create(['name' => 'Item A', 'code' => 'ITEMA', 'stock_unit_id' => $this->unit->id]);

    $this->service = app(FreightServiceInterface::class);
});

test('godownWiseReport aggregates delivery note movements per godown', function () {
    createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 100, 10, 'in');
    createDeliveryNote('DN-0002', '2025-07-01', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 30, 10, 'out');
    createDeliveryNote('DN-0003', '2025-08-01', $this->item, $this->unit, $this->godownB, $this->fiscalYear->id, 50, 12, 'in');

    $result = $this->service->godownWiseReport();

    expect($result)->toHaveCount(2);

    $godownA = $result->firstWhere('godownId', $this->godownA->id);
    expect($godownA['godownName'])->toBe('Godown A');
    expect($godownA['totalInwardQuantity'])->toBe(100.0);
    expect($godownA['totalOutwardQuantity'])->toBe(30.0);
    expect($godownA['totalClosingQuantity'])->toBe(70.0);
    expect($godownA['totalEntries'])->toBe(2);
    expect($godownA['voucherDetails'])->toHaveCount(2);

    $godownB = $result->firstWhere('godownId', $this->godownB->id);
    expect($godownB['totalInwardQuantity'])->toBe(50.0);
    expect($godownB['totalClosingQuantity'])->toBe(50.0);
    expect($godownB['voucherDetails'][0]['voucherNo'])->toBe('DN-0003');
});

test('godownWiseReport aggregates correctly across chunk boundaries', function () {
    // More delivery notes than the 200-row chunk size — forces multiple chunks.
    for ($i = 1; $i <= 205; $i++) {
        createDeliveryNote(
            "DN-BULK-{$i}",
            '2025-06-15',
            $this->item,
            $this->unit,
            $this->godownA,
            $this->fiscalYear->id,
            1,
            10,
            'in'
        );
    }

    $result = $this->service->godownWiseReport();

    expect($result)->toHaveCount(1);

    $godownA = $result->firstWhere('godownId', $this->godownA->id);
    expect($godownA['totalInwardQuantity'])->toBe(205.0);
    expect($godownA['totalOutwardQuantity'])->toBe(0);
    expect($godownA['totalClosingQuantity'])->toBe(205.0);
    expect($godownA['totalEntries'])->toBe(205);
    expect($godownA['voucherDetails'])->toHaveCount(205);
});

test('godownWiseReport respects the reporting period boundaries', function () {
    $this->userFiscalYear->update(['end_date' => '2025-09-30']);

    createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 100, 10, 'in');
    createDeliveryNote('DN-0002', '2025-12-20', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 50, 10, 'in');

    $result = $this->service->godownWiseReport();

    expect($result)->toHaveCount(1);
    expect($result->firstWhere('godownId', $this->godownA->id)['totalInwardQuantity'])->toBe(100.0);
});

test('deliveryNoteGodownWiseReport filters to the selected godown', function () {
    createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 100, 10, 'in');
    createDeliveryNote('DN-0002', '2025-07-01', $this->item, $this->unit, $this->godownB, $this->fiscalYear->id, 50, 12, 'in');

    $result = $this->service->deliveryNoteGodownWiseReport(godownId: $this->godownA->id);

    expect($result)->toHaveCount(1);
    expect($result[0]['godownId'])->toBe($this->godownA->id);
    expect($result[0]['totalInwardQuantity'])->toBe(100.0);
});

test('deliveryNoteZoneWiseReport groups delivery notes by zone', function () {
    $zone = Godown::create(['name' => 'Zone A', 'code' => 'ZONEA', 'storage_unit_type' => 'ZONE']);
    $zoneGodown = Godown::create(['name' => 'Zone Godown', 'code' => 'ZG1', 'parent_id' => $zone->id]);

    createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 100, 10, 'in');
    createDeliveryNote('DN-0002', '2025-07-01', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 30, 10, 'out');

    $result = $this->service->deliveryNoteZoneWiseReport();

    expect($result)->toHaveCount(2);

    $zoneBucket = $result->firstWhere('zoneId', $zone->id);
    expect($zoneBucket['zoneName'])->toBe('Zone A');
    expect($zoneBucket['totalInwardQuantity'])->toBe(100.0);

    $unmapped = $result->firstWhere('zoneId', null);
    expect($unmapped['zoneName'])->toBe('Unmapped');
    expect($unmapped['totalOutwardQuantity'])->toBe(30.0);
});

test('transporterItemWiseReport groups freight entries by transporter', function () {
    $deliveryNote = createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 100, 10, 'in');

    VoucherDispatchDetail::create([
        'voucher_id' => $deliveryNote->id,
        'carrier_name' => 'Truck Co',
        'motor_vehicle_no' => 'MH-01-1234',
        'source' => 'Pune',
        'destination' => 'Mumbai',
        'total_fare' => 500,
    ]);

    createFreightVoucher($deliveryNote, $this->fiscalYear->id, 'FR-0001', '2025-06-20');

    $result = $this->service->transporterItemWiseReport();

    expect($result)->toHaveCount(1);

    $truckCo = $result[0];
    expect($truckCo['transporterName'])->toBe('Truck Co');
    expect($truckCo['vehicleNumber'])->toBe('MH-01-1234');
    expect($truckCo['totalVouchers'])->toBe(1);
    expect($truckCo['totalQuantity'])->toBe(100.0);
    expect($truckCo['entries'])->toHaveCount(1);
    expect($truckCo['entries'][0]['voucherNo'])->toBe('FR-0001');
    expect($truckCo['entries'][0]['totalFare'])->toBe(500.0);
});

test('getDeliveryNote filters delivery notes by zone', function () {
    $zone = Godown::create(['name' => 'Zone A', 'code' => 'ZONEA', 'storage_unit_type' => 'ZONE']);
    $zoneGodown = Godown::create(['name' => 'Zone Godown', 'code' => 'ZG1', 'parent_id' => $zone->id]);

    createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 100, 10, 'in');
    createDeliveryNote('DN-0002', '2025-07-01', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 30, 10, 'out');

    $result = $this->service->getDeliveryNote(1, 10, [
        'zone_id' => $zone->id,
        'freight_status' => 'all',
    ]);

    expect($result)->toHaveCount(1);
    expect($result->first()->voucher_no)->toBe('DN-0001');
});

test('getDeliveryNoteOverallTotalFare respects the zone filter', function () {
    $zone = Godown::create(['name' => 'Zone A', 'code' => 'ZONEA', 'storage_unit_type' => 'ZONE']);
    $zoneGodown = Godown::create(['name' => 'Zone Godown', 'code' => 'ZG1', 'parent_id' => $zone->id]);

    $dn1 = createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 100, 10, 'in');
    $dn2 = createDeliveryNote('DN-0002', '2025-07-01', $this->item, $this->unit, $this->godownA, $this->fiscalYear->id, 30, 10, 'out');

    VoucherDispatchDetail::create([
        'voucher_id' => $dn1->id,
        'total_fare' => 500,
        'freight_charges' => 500,
    ]);
    VoucherDispatchDetail::create([
        'voucher_id' => $dn2->id,
        'total_fare' => 300,
        'freight_charges' => 300,
    ]);

    $total = $this->service->getDeliveryNoteOverallTotalFare([
        'zone_id' => $zone->id,
        'freight_status' => 'all',
    ]);

    expect($total)->toBe(500.0);
});

test('zoneWiseReport groups freight vouchers by zone', function () {
    $zone = Godown::create(['name' => 'Zone A', 'code' => 'ZONEA', 'storage_unit_type' => 'ZONE']);
    $zoneGodown = Godown::create(['name' => 'Zone Godown', 'code' => 'ZG1', 'parent_id' => $zone->id]);

    $deliveryNote = createDeliveryNote('DN-0001', '2025-06-15', $this->item, $this->unit, $zoneGodown, $this->fiscalYear->id, 100, 10, 'in');
    createFreightVoucher($deliveryNote, $this->fiscalYear->id, 'FR-0001', '2025-06-20');

    $result = $this->service->zoneWiseReport();

    expect($result)->toHaveCount(1);

    $zoneBucket = $result->firstWhere('zoneId', $zone->id);
    expect($zoneBucket['zoneName'])->toBe('Zone A');
    expect($zoneBucket['totalInwardQuantity'])->toBe(100.0);
    expect($zoneBucket['godownDetails'][0]['voucherNo'])->toBe('FR-0001');
});
