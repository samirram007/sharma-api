<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\FiscalYearClose\Services\FiscalYearCloseService;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherType\Models\VoucherType;

/**
 * Helper to invoke protected/private methods via reflection.
 */
function invokeProtectedMethod(object $object, string $method, array $parameters = []): mixed
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($object, $parameters);
}

/**
 * Build a fake DB row object matching the stock data query result shape.
 */
function fakeStockRow(int $itemId, int $godownId, int $unitId, float $netQty): stdClass
{
    return (object) [
        'stock_item_id' => $itemId,
        'godown_id' => $godownId,
        'stock_unit_id' => $unitId,
        'net_quantity' => $netQty,
    ];
}

/**
 * Create a minimally-stubbed FiscalYear.
 */
function makeFiscalYear(): FiscalYear
{
    $fy = Mockery::mock(FiscalYear::class)->makePartial();
    $fy->id = 99;
    $fy->name = 'Test FY 2025-26';
    $fy->start_date = '2025-04-01';
    $fy->end_date = '2026-03-31';
    $fy->status = 'active';

    return $fy;
}

/**
 * Create a minimally-stubbed VoucherType.
 */
function makeVoucherType(): VoucherType
{
    $vt = Mockery::mock(VoucherType::class)->makePartial();
    $vt->id = 42;
    $vt->name = 'Closing Stock';
    $vt->code = 'CLSSK';
    $vt->status = 'active';

    return $vt;
}

/**
 * Drop and recreate the vouchers table for test isolation.
 */
function resetVouchersTable(): void
{
    Schema::dropIfExists('vouchers');
    Schema::create('vouchers', function (Blueprint $table) {
        $table->id();
        $table->string('voucher_no');
        $table->date('voucher_date');
        $table->unsignedBigInteger('voucher_type_id')->default(1);
        $table->unsignedBigInteger('fiscal_year_id')->default(1);
        $table->unsignedBigInteger('stock_journal_id')->nullable();
        $table->string('module')->nullable();
        $table->boolean('is_effecting')->default(true);
        $table->boolean('effects_account')->default(true);
        $table->boolean('effects_stock')->default(false);
        $table->text('remarks')->nullable();
        $table->string('status')->default('active');
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();
    });
}

beforeEach(function () {
    // Ensure the vouchers table exists in the SQLite in-memory DB (created once per process)
    if (! Schema::hasTable('vouchers')) {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no');
            $table->date('voucher_date');
            $table->unsignedBigInteger('voucher_type_id')->default(1);
            $table->unsignedBigInteger('fiscal_year_id')->default(1);
            $table->unsignedBigInteger('stock_journal_id')->nullable();
            $table->string('module')->nullable();
            $table->boolean('is_effecting')->default(true);
            $table->boolean('effects_account')->default(true);
            $table->boolean('effects_stock')->default(false);
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    // Build a reusable DB query builder mock.
    // createClosingStockVoucher() does two DB queries:
    //   1. Main stock data: DB::table('stock_journal_godown_entries')->join()->...->get()
    //   2. Avg rate:        DB::table('stock_journal_entries')->join()->...->value('avg_rate')
    // The closure in whereNotExists receives a separate subquery builder — since Mockery's
    // demeter mock returns the same instance for every method, the closure's from/whereColumn
    // calls are handled automatically.
    $this->queryMock = Mockery::mock('queryMock');
    $this->queryMock->shouldReceive('join')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('where')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('whereNotExists')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('selectRaw')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('groupBy')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('having')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('from')->andReturn($this->queryMock);
    $this->queryMock->shouldReceive('whereColumn')->andReturn($this->queryMock);
    // Default: getItemAverageRate() returns 0.0
    $this->queryMock->shouldReceive('value')->with('avg_rate')->andReturn(0.0);

    DB::shouldReceive('table')->andReturn($this->queryMock);

    // Mock the three service dependencies
    $this->voucherEntryService = Mockery::mock(VoucherEntryServiceInterface::class);
    $this->stockJournalService = Mockery::mock(StockJournalServiceInterface::class);
    $this->stockJournalEntrySvc = Mockery::mock(StockJournalEntryServiceInterface::class);

    // Bind mocks into the container so app() resolves them
    $this->app->instance(VoucherEntryServiceInterface::class, $this->voucherEntryService);
    $this->app->instance(StockJournalServiceInterface::class, $this->stockJournalService);
    $this->app->instance(StockJournalEntryServiceInterface::class, $this->stockJournalEntrySvc);

    $this->service = new FiscalYearCloseService(
        $this->voucherEntryService,
        $this->stockJournalService,
        $this->stockJournalEntrySvc,
    );
});

afterEach(function () {
    Mockery::close();
});

// ---------------------------------------------------------------------------
//  Tests
// ---------------------------------------------------------------------------

test('IN movement when net quantity is positive', function () {
    $fy = makeFiscalYear();
    $vt = makeVoucherType();

    // Set up stock data result
    $this->queryMock->shouldReceive('get')
        ->once()
        ->andReturn(collect([fakeStockRow(1, 1, 1, 70.0)]));

    // Mock StockJournal creation
    $fakeSj = Mockery::mock(StockJournal::class)->makePartial();
    $fakeSj->id = 201;
    $this->stockJournalService
        ->shouldReceive('store')
        ->once()
        ->andReturn($fakeSj);

    // Capture the entry passed to stockJournalEntrySvc->store()
    $capturedEntry = null;
    $this->stockJournalEntrySvc
        ->shouldReceive('store')
        ->once()
        ->with(Mockery::on(function ($data) use (&$capturedEntry) {
            $capturedEntry = $data;

            return true;
        }))
        ->andReturn(Mockery::mock(StockJournalEntry::class)->makePartial());

    // Voucher::create() will run against the real SQLite vouchers table
    $voucher = invokeProtectedMethod($this->service, 'createClosingStockVoucher', [$fy, $vt]);

    // Verify the voucher was created in the database
    expect($voucher)->toBeInstanceOf(Voucher::class);
    expect($voucher->voucher_no)->toContain('CLSSK-');
    expect($voucher->fiscal_year_id)->toBe(99);
    expect($voucher->stock_journal_id)->toBe(201);

    // Assert parent entry has IN movement
    expect($capturedEntry)->not->toBeNull();
    expect($capturedEntry['movement_type'])->toBe('in');
    expect($capturedEntry['actual_quantity'])->toBe(70.0);
    expect($capturedEntry['stock_item_id'])->toBe(1);

    // Assert godown entry has IN movement
    expect($capturedEntry['stock_journal_godown_entries'])->toHaveCount(1);
    $ge = $capturedEntry['stock_journal_godown_entries'][0];
    expect($ge['movement_type'])->toBe('in');
    expect($ge['actual_quantity'])->toBe(70.0);
    expect($ge['godown_id'])->toBe(1);
});

test('OUT movement when net quantity is negative', function () {
    $fy = makeFiscalYear();
    $vt = makeVoucherType();

    $this->queryMock->shouldReceive('get')
        ->once()
        ->andReturn(collect([fakeStockRow(2, 1, 1, -30.0)]));

    $fakeSj = Mockery::mock(StockJournal::class)->makePartial();
    $fakeSj->id = 202;
    $this->stockJournalService
        ->shouldReceive('store')
        ->once()
        ->andReturn($fakeSj);

    $capturedEntry = null;
    $this->stockJournalEntrySvc
        ->shouldReceive('store')
        ->once()
        ->with(Mockery::on(function ($data) use (&$capturedEntry) {
            $capturedEntry = $data;

            return true;
        }))
        ->andReturn(Mockery::mock(StockJournalEntry::class)->makePartial());

    $voucher = invokeProtectedMethod($this->service, 'createClosingStockVoucher', [$fy, $vt]);

    expect($voucher)->toBeInstanceOf(Voucher::class);

    // Assert parent entry has OUT movement
    expect($capturedEntry)->not->toBeNull();
    expect($capturedEntry['movement_type'])->toBe('out');
    expect($capturedEntry['actual_quantity'])->toBe(30.0);
    expect($capturedEntry['stock_item_id'])->toBe(2);

    // Assert godown entry has OUT movement
    expect($capturedEntry['stock_journal_godown_entries'])->toHaveCount(1);
    $ge = $capturedEntry['stock_journal_godown_entries'][0];
    expect($ge['movement_type'])->toBe('out');
    expect($ge['actual_quantity'])->toBe(30.0);
});

test('mixed IN and OUT for different items in same fiscal year close', function () {
    $fy = makeFiscalYear();
    $vt = makeVoucherType();

    $this->queryMock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            fakeStockRow(10, 1, 1, 70.0),
            fakeStockRow(20, 1, 1, -30.0),
        ]));

    $fakeSj = Mockery::mock(StockJournal::class)->makePartial();
    $fakeSj->id = 203;
    $this->stockJournalService
        ->shouldReceive('store')
        ->once()
        ->andReturn($fakeSj);

    $capturedEntries = [];
    $this->stockJournalEntrySvc
        ->shouldReceive('store')
        ->times(2)
        ->with(Mockery::on(function ($data) use (&$capturedEntries) {
            $capturedEntries[] = $data;

            return true;
        }))
        ->andReturn(Mockery::mock(StockJournalEntry::class)->makePartial());

    $voucher = invokeProtectedMethod($this->service, 'createClosingStockVoucher', [$fy, $vt]);

    expect($voucher)->toBeInstanceOf(Voucher::class);
    expect($capturedEntries)->toHaveCount(2);

    $itemA = collect($capturedEntries)->firstWhere('stock_item_id', 10);
    expect($itemA['movement_type'])->toBe('in');
    expect($itemA['actual_quantity'])->toBe(70.0);

    $itemB = collect($capturedEntries)->firstWhere('stock_item_id', 20);
    expect($itemB['movement_type'])->toBe('out');
    expect($itemB['actual_quantity'])->toBe(30.0);
});

test('throws exception when no stock data exists', function () {
    $fy = makeFiscalYear();
    $vt = makeVoucherType();

    $this->queryMock->shouldReceive('get')
        ->once()
        ->andReturn(collect([]));

    invokeProtectedMethod($this->service, 'createClosingStockVoucher', [$fy, $vt]);
})->throws(Exception::class, 'No stock quantities to close');

test('voucher metadata is correct', function () {
    $fy = makeFiscalYear();
    $vt = makeVoucherType();

    $this->queryMock->shouldReceive('get')
        ->once()
        ->andReturn(collect([fakeStockRow(1, 1, 1, 50.0)]));

    $fakeSj = Mockery::mock(StockJournal::class)->makePartial();
    $fakeSj->id = 204;
    $this->stockJournalService
        ->shouldReceive('store')
        ->once()
        ->andReturn($fakeSj);

    $this->stockJournalEntrySvc
        ->shouldReceive('store')
        ->once()
        ->andReturn(Mockery::mock(StockJournalEntry::class)->makePartial());

    $voucher = invokeProtectedMethod($this->service, 'createClosingStockVoucher', [$fy, $vt]);

    expect($voucher->voucher_no)->toContain('CLSSK-');
    expect($voucher->remarks)->toContain('Stock closing');
    expect($voucher->status)->toBe('active');
    expect($voucher->effects_stock)->toBeTrue();
    expect($voucher->effects_account)->toBeFalse();
    expect($voucher->module)->toBe('system');
    expect($voucher->stock_journal_id)->toBe(204);
});
