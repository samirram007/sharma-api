<?php

namespace Modules\Role\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Role\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id' => 10000,
                'name' => 'Super Admin',
                'code' => 'SUPER_ADMIN',
                'status' => 'active',
            ],
            [
                'id' => 10001,
                'name' => 'Admin',
                'code' => 'ADMIN',
                'status' => 'active',
            ],
            [
                'id' => 10002,
                'name' => 'Developer',
                'code' => 'DEVELOPER',
                'status' => 'active',
            ],
            [
                'id' => 10003,
                'name' => 'Manager',
                'code' => 'MANAGER',
                'status' => 'active',
            ],
            [
                'id' => 10004,
                'name' => 'Employee',
                'code' => 'EMPLOYEE',
                'status' => 'active',
            ],
            [
                'id' => 10005,
                'name' => 'Supplier',
                'code' => 'SUPPLIER',
                'status' => 'active',
            ],
            [
                'id' => 10006,
                'name' => 'Agent',
                'code' => 'AGENT',
                'status' => 'active',
            ],
            [
                'id' => 10007,
                'name' => 'Received Note Employee',
                'code' => 'RECEIPT_NOTE_EMPLOYEE',
                'status' => 'active',
            ],
            [
                'id' => 10008,
                'name' => 'Delivery Note Employee',
                'code' => 'DELIVERY_NOTE_EMPLOYEE',
                'status' => 'active',
            ],
            [
                'id' => 10009,
                'name' => 'Freight Employee',
                'code' => 'FREIGHT_EMPLOYEE',
                'status' => 'active',
            ],
            [
                'id' => 10010,
                'name' => 'Conversion Employee',
                'code' => 'CONVERSION_EMPLOYEE',
                'status' => 'active',
            ],
            [
                'id' => 10011,
                'name' => 'Physical Stock Employee',
                'code' => 'PHYSICAL_STOCK_EMPLOYEE',
                'status' => 'active',
            ],
            [
                'id' => 10012,
                'name' => 'Opening Stock Employee',
                'code' => 'OPENING_STOCK_EMPLOYEE',
                'status' => 'active',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                $role
            );
        }

        // Ensure auto-increment starts after last seeded ID
        // \DB::statement("ALTER TABLE roles AUTO_INCREMENT = 10004;");
    }
}
