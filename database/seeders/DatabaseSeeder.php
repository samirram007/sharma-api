<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Modules\AppModule\Database\Seeders\AppModuleSeeder;
use Modules\Menu\Database\Seeders\MenuSeeder;
use Modules\Currency\Database\Seeders\CurrencySeeder;
use Modules\Department\Database\Seeders\DepartmentSeeder;
use Modules\Department\Models\Department;
use Modules\Designation\Database\Seeders\DesignationSeeder;
use Modules\Distributor\Database\Seeders\DistributorSeeder;
use Modules\EmployeeGroup\Database\Seeders\EmployeeGroupSeeder;
use Modules\Grade\Database\Seeders\GradeSeeder;
use Modules\Status\Database\Seeders\StatusSeeder;
use Modules\GstRegistrationType\Database\Seeders\GstRegistrationTypeSeeder;
use Modules\GstRegistrationType\Models\GstRegistrationType;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Shift\Database\Seeders\ShiftSeeder;
use Modules\Supplier\Database\Seeders\SupplierSeeder;
use Modules\UserFiscalYear\Database\Seeders\UserFiscalYearSeeder;

use Illuminate\Database\Seeder;

use Database\Seeders\SampleDataSeeder;

use Modules\State\Database\Seeders\StateSeeder;
use Modules\StockCategory\Models\StockCategory;
use Modules\Godown\Database\Seeders\GodownSeeder;
use Modules\Company\Database\Seeders\CompanySeeder;
use Modules\Country\Database\Seeders\CountrySeeder;
use Modules\Purchase\Database\Seeders\PurchaseSeeder;
use Modules\StockItem\Database\Seeders\StockItemSeeder;
use Modules\StockUnit\Database\Seeders\StockUnitSeeder;
use Modules\FiscalYear\Database\Seeders\FiscalYearSeeder;
use Modules\StockGroup\Database\Seeders\StockGroupSeeder;
use Modules\UniqueQuantityCode\Models\UniqueQuantityCode;
use Modules\CompanyType\Database\Seeders\CompanyTypeSeeder;
use Modules\VoucherType\Database\Seeders\VoucherTypeSeeder;

use Modules\AccountGroup\Database\Seeders\AccountGroupSeeder;
use Modules\AccountLedger\Database\Seeders\AccountLedgerSeeder;
use Modules\AccountNature\Database\Seeders\AccountNatureSeeder;
use Modules\StockCategory\Database\Seeders\StockCategorySeeder;
use Modules\VoucherClassification\Models\VoucherClassification;
use Modules\VoucherCategory\Database\Seeders\VoucherCategorySeeder;
use Modules\UniqueQuantityCode\Database\Seeders\UniqueQuantityCodeSeeder;
use Modules\VoucherClassification\Database\Seeders\VoucherClassificationSeeder;


class DatabaseSeeder extends Seeder
{

    protected static ?string $password;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        $this->call([

            // AppModuleSeeder::class,
            MenuSeeder::class,
            // RoleSeeder::class,

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
