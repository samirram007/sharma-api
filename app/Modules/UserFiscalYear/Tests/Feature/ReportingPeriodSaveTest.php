<?php

namespace Modules\UserFiscalYear\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Regression tests for POST /api/reporting_period.
 *
 * Bug: changing the reporting period from the Period dialog did not persist —
 * user_fiscal_years.start_date/end_date stayed at their previous values, so
 * every report kept the old date window.
 *
 * Root cause: the frontend sends the dates as JS Date objects serialized to
 * ISO-8601 ("2026-09-01T00:00:00.000Z"). PHP's strtotime does parse the date
 * portion of that, so validation passes — but a payload sent as JSON strings
 * (the common axios path) must keep working too, and the dates must land in
 * the DB exactly as the user picked them.
 */
class ReportingPeriodSaveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Reporting Period Tester',
            'email' => 'reporting-period-tester@example.com',
            'password' => 'password',
        ]);
        $this->token = JWTAuth::fromUser($this->user);

        DB::table('fiscal_years')->insert([
            'company_id' => 1,
            'name' => 'FY Test',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_fiscal_years')->insert([
            'user_id' => $this->user->id,
            'fiscal_year_id' => 1,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'current_date' => '2026-09-19',
        ]);
    }

    public function test_saves_iso8601_datetime_strings_from_frontend_dates(): void
    {
        // Exact shape axios produces for JS Date values.
        $response = $this->withToken($this->token)
            ->postJson('/api/reporting_period', [
                'startDate' => '2026-09-01T00:00:00.000Z',
                'endDate' => '2026-09-19T00:00:00.000Z',
            ])
            ->assertOk();

        expect($response->json('message'))->toBe('Reporting period saved successfully');

        $row = DB::table('user_fiscal_years')
            ->where('user_id', $this->user->id)
            ->first();

        // The DATE portion must be preserved exactly — no day-shift from
        // ISO-UTC serialization being re-cast in a non-UTC app timezone.
        expect(substr($row->start_date, 0, 10))->toBe('2026-09-01')
            ->and(substr($row->end_date, 0, 10))->toBe('2026-09-19');
    }

    public function test_saves_plain_yyyy_mm_dd_strings(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/reporting_period', [
                'startDate' => '2026-08-01',
                'endDate' => '2026-08-31',
            ])
            ->assertOk();

        $row = DB::table('user_fiscal_years')
            ->where('user_id', $this->user->id)
            ->first();

        expect(substr($row->start_date, 0, 10))->toBe('2026-08-01')
            ->and(substr($row->end_date, 0, 10))->toBe('2026-08-31');
    }

    public function test_rejects_end_date_before_start_date(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/reporting_period', [
                'startDate' => '2026-09-19',
                'endDate' => '2026-09-01',
            ])
            ->assertStatus(422);
    }

    public function test_requires_both_dates(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/reporting_period', ['startDate' => '2026-09-01'])
            ->assertStatus(422);

        $this->withToken($this->token)
            ->postJson('/api/reporting_period', ['endDate' => '2026-09-19'])
            ->assertStatus(422);
    }

    public function test_guest_cannot_save_reporting_period(): void
    {
        $this->postJson('/api/reporting_period', [
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-19',
        ])->assertStatus(401);
    }
}
