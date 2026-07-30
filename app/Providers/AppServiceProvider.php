<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Modules\Agent\Models\Agent;
use Modules\Company\Models\Company;
use Modules\Customer\Models\Customer;
use Modules\DeliveryPlace\Models\DeliveryPlace;
use Modules\Distributor\Models\Distributor;
use Modules\Employee\Models\Employee;
use Modules\Godown\Models\Godown;
use Modules\StorageUnit\Models\StorageUnit;
use Modules\Supplier\Models\Supplier;
use Modules\Transporter\Models\Transporter;
use Modules\Vendor\Models\Vendor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([

            'agent' => Agent::class,
            'customer' => Customer::class,
            'distributor' => Distributor::class,
            'employee' => Employee::class,
            'godown' => Godown::class,
            'supplier' => Supplier::class,
            'transporter' => Transporter::class,
            'vendor' => Vendor::class,
            'delivery_place' => DeliveryPlace::class,
            'company' => Company::class,
            'storage_unit' => StorageUnit::class,
        ]);
    }
}
