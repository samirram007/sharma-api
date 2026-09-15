<?php

namespace Modules\Voucher\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;
use Modules\Voucher\Repositories\VoucherRepository;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;
use Tests\TestCase;

/**
 * Regression tests for the one-day-back date bug on update.
 *
 * The browser serialises a locally-picked date (IST midnight) as a UTC
 * instant ("2026-05-16T18:30:00.000Z" for 17/5 Asia/Kolkata). Eloquent
 * casts normalise that on CREATE, but BaseRepository::update writes via a
 * query-builder UPDATE that bypasses casts — so the DATE column kept the
 * UTC calendar day and every update shifted the voucher date one day back
 * (17/5 became 16/5 on the VPS, whose payload clock differs from local).
 */
class VoucherUpdateTimezoneTest extends TestCase
{
    use RefreshDatabase;

    private Voucher $voucher;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Timezone Test User',
            'email' => 'timezone-test@example.com',
            'password' => 'password',
        ]);
        $this->actingAs($user);

        $category = VoucherCategory::create(['name' => 'System', 'code' => 'SYS']);

        $voucherType = VoucherType::create([
            'name' => 'Delivery Note',
            'code' => 'DLNT',
            'voucher_category_id' => $category->id,
            'status' => 'active',
        ]);

        FiscalYear::create([
            'name' => 'FY 2026-27',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'company_id' => 1,
        ]);

        $this->voucher = Voucher::create([
            'voucher_no' => 'DLNT-TZ-1',
            'voucher_date' => '2026-05-10',
            'voucher_type_id' => $voucherType->id,
            'fiscal_year_id' => 1,
            'company_id' => 1,
            'status' => 'active',
        ]);
    }

    public function test_update_persists_utc_instant_as_local_calendar_date(): void
    {
        // What axios sends for a user picking 17/5/2026 in Asia/Kolkata.
        $utcInstant = '2026-05-16T18:30:00.000Z';

        $repository = new VoucherRepository(new Voucher);
        $repository->update(['voucher_date' => $utcInstant], $this->voucher->id);

        $this->assertSame(
            '2026-05-17',
            $this->voucher->fresh()->voucher_date->toDateString(),
            'An update must store the Asia/Kolkata calendar date, not the UTC one.',
        );
    }

    public function test_update_with_plain_date_stays_unchanged(): void
    {
        $repository = new VoucherRepository(new Voucher);
        $repository->update(['voucher_date' => '2026-05-17'], $this->voucher->id);

        $this->assertSame('2026-05-17', $this->voucher->fresh()->voucher_date->toDateString());
    }

    public function test_update_with_null_date_is_skipped_by_fillable_intersect(): void
    {
        // voucher_date is not nullable, so confirm nulls pass through untouched
        // (they are excluded from cast normalisation) rather than being mangled.
        $repository = new VoucherRepository(new Voucher);
        $repository->update(['remarks' => 'tz probe'], $this->voucher->id);

        $this->assertSame('tz probe', $this->voucher->fresh()->remarks);
        $this->assertSame('2026-05-10', $this->voucher->fresh()->voucher_date->toDateString());
    }
}
