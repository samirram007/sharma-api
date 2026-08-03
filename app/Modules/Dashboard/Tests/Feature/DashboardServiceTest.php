<?php

namespace Modules\Dashboard\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dashboard\Contracts\DashboardServiceInterface;
use Modules\Distributor\Models\Distributor;
use Modules\Godown\Models\Godown;
use Modules\Transporter\Models\Transporter;
use Modules\User\Models\User;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DashboardServiceInterface::class);
    }

    public function test_summary_returns_stat_card_counts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Transporter::create(['name' => 'ABC Transports', 'code' => 'TR-001']);
        Distributor::create(['name' => 'XYZ Distributor', 'code' => 'DI-001']);
        Godown::create([
            'name' => 'Warehouse Zone',
            'code' => 'LOC-000001',
            'storage_unit_type' => 'ZONE',
        ]);

        $summary = $this->service->summary();

        $this->assertArrayHasKey('transporterCount', $summary);
        $this->assertArrayHasKey('distributorCount', $summary);
        $this->assertArrayHasKey('zoneCount', $summary);
        $this->assertArrayHasKey('godownCount', $summary);
        $this->assertArrayHasKey('userCount', $summary);
        $this->assertArrayHasKey('freightCount', $summary);
        $this->assertArrayHasKey('freightTotalFare', $summary);
        $this->assertArrayHasKey('deliveryNoteCount', $summary);
        $this->assertArrayHasKey('receiptNoteCount', $summary);
        $this->assertArrayHasKey('paymentCount', $summary);
        $this->assertArrayHasKey('paymentTotal', $summary);
        $this->assertArrayHasKey('currentFiscalYear', $summary);

        $this->assertSame(1, $summary['transporterCount']);
        $this->assertSame(1, $summary['distributorCount']);
        $this->assertSame(1, $summary['zoneCount']);
        $this->assertSame(1, $summary['godownCount']);
        $this->assertSame(1, $summary['userCount']);
    }

    public function test_summary_handles_missing_user_fiscal_year(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $summary = $this->service->summary();

        $this->assertNull($summary['currentFiscalYear']);
        $this->assertSame(0, $summary['freightCount']);
        $this->assertSame(0, $summary['deliveryNoteCount']);
        $this->assertSame(0, $summary['receiptNoteCount']);
    }

    public function test_user_wise_returns_collection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $result = $this->service->userWise();

        $this->assertInstanceOf(Collection::class, $result);
    }
}
