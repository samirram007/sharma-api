<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\AccountGroup\Database\Seeders\AccountGroupSeeder;
use Modules\AccountLedger\Database\Seeders\AccountLedgerSeeder;
use Modules\AccountNature\Database\Seeders\AccountNatureSeeder;
use Modules\AppModule\Database\Seeders\AppModuleSeeder;
use Modules\AppModuleFeature\Database\Seeders\AppModuleFeatureSeeder;
use Modules\AppModuleFeature\Database\Seeders\MenuFeatureSeeder;
use Modules\Company\Database\Seeders\CompanySeeder;
use Modules\CompanyType\Database\Seeders\CompanyTypeSeeder;
use Modules\Country\Database\Seeders\CountrySeeder;
use Modules\Currency\Database\Seeders\CurrencySeeder;
use Modules\Department\Database\Seeders\DepartmentSeeder;
use Modules\Designation\Database\Seeders\DesignationSeeder;
use Modules\Distributor\Database\Seeders\DistributorSeeder;
use Modules\EmployeeGroup\Database\Seeders\EmployeeGroupSeeder;
use Modules\FiscalYear\Database\Seeders\FiscalYearSeeder;
use Modules\Godown\Database\Seeders\GodownSeeder;
use Modules\Grade\Database\Seeders\GradeSeeder;
use Modules\GstRegistrationType\Database\Seeders\GstRegistrationTypeSeeder;
use Modules\Menu\Database\Seeders\MenuSeeder;
use Modules\Purchase\Database\Seeders\PurchaseSeeder;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\RolePermission\Database\Seeders\RolePermissionSeeder;
use Modules\Shift\Database\Seeders\ShiftSeeder;
use Modules\State\Database\Seeders\StateSeeder;
use Modules\Status\Database\Seeders\StatusSeeder;
use Modules\StockCategory\Database\Seeders\StockCategorySeeder;
use Modules\StockGroup\Database\Seeders\StockGroupSeeder;
use Modules\StockItem\Database\Seeders\StockItemSeeder;
use Modules\StockUnit\Database\Seeders\StockUnitSeeder;
use Modules\Supplier\Database\Seeders\SupplierSeeder;
use Modules\UniqueQuantityCode\Database\Seeders\UniqueQuantityCodeSeeder;
use Modules\UserFiscalYear\Database\Seeders\UserFiscalYearSeeder;
use Modules\VoucherCategory\Database\Seeders\VoucherCategorySeeder;
use Modules\VoucherClassification\Database\Seeders\VoucherClassificationSeeder;
use Modules\VoucherType\Database\Seeders\VoucherTypeSeeder;

class DatabaseSeeder extends Seeder
{
    protected static ?string $password;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            AppModuleSeeder::class,
            AppModuleFeatureSeeder::class,
            MenuFeatureSeeder::class,
            RoleSeeder::class,
            MenuSeeder::class,
            RolePermissionSeeder::class,

            // GstRegistrationTypeSeeder::class,

            // CurrencySeeder::class,
            // CountrySeeder::class,
            // StateSeeder::class,
            // SampleDataSeeder::class,
            // AccountNatureSeeder::class,
            //     // AccountGroupSeeder::class,
            //     // AccountLedgerSeeder::class,
            // CompanyTypeSeeder::class,
            // FiscalYearSeeder::class,
            //     // CompanySeeder::class,

            // VoucherCategorySeeder::class,
            // VoucherTypeSeeder::class,
            // VoucherClassificationSeeder::class,

            // UniqueQuantityCodeSeeder::class,
            // StockUnitSeeder::class,
            // StockGroupSeeder::class,
            // StockCategorySeeder::class,

            // StockItemSeeder::class,
            //     // PurchaseSeeder::class
            // GodownSeeder::class,
            // DepartmentSeeder::class,
            // DesignationSeeder::class,
            // GradeSeeder::class,
            // StatusSeeder::class,
            //     // ShiftSeeder::class,

            //     // VoucherSeeder::class,
            //     // VoucherEntrySeeder::class,

            // EmployeeGroupSeeder::class,
            // SupplierSeeder::class,
            // DistributorSeeder::class,
            // UserFiscalYearSeeder::class,
            // VoucherDemoSeeder::class,
            // ReceiptNoteSeeder::class,

        ]);
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'password' => static::$password ??= Hash::make('password'),
        // ]);
        // \App\Models\User::create([
        //     'name' => 'Admin User',
        //     'user_type'=>'admin',
        //     'email' => 'admin@admin.com',
        //     'email_verified_at' => now(),
        //     'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        //     'remember_token' => Str::random(10),
        // ]);
        // \App\Models\User::create([
        //     'name' => 'Manager User',
        //     'user_type'=>'user',
        //     'email' => 'manager@admin.com',
        //     'email_verified_at' => now(),
        //     'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        //     'remember_token' => Str::random(10),
        // ]);

    }
}
