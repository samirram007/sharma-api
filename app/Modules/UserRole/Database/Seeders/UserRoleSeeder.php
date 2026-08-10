<?php

namespace Modules\UserRole\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Modules\UserRole\Models\UserRole;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users pre-linked to the module-specific Employee roles.
        // Each user logs in with password 'password' and only sees its own
        // module in the side menu and top menu.
        $assignments = [
            [
                'email' => 'received@admin.com',
                'name' => 'Received Note User',
                'role_code' => 'RECEIPT_NOTE_EMPLOYEE',
            ],
            [
                'email' => 'delivery@admin.com',
                'name' => 'Delivery Note User',
                'role_code' => 'DELIVERY_NOTE_EMPLOYEE',
            ],
            [
                'email' => 'freight@admin.com',
                'name' => 'Freight User',
                'role_code' => 'FREIGHT_EMPLOYEE',
            ],
            [
                'email' => 'conversion@admin.com',
                'name' => 'Conversion User',
                'role_code' => 'CONVERSION_EMPLOYEE',
            ],
            [
                'email' => 'physical@admin.com',
                'name' => 'Physical Stock User',
                'role_code' => 'PHYSICAL_STOCK_EMPLOYEE',
            ],
            [
                'email' => 'opening@admin.com',
                'name' => 'Opening Stock User',
                'role_code' => 'OPENING_STOCK_EMPLOYEE',
            ],
        ];

        $count = 0;
        foreach ($assignments as $assignment) {
            $role = Role::where('code', $assignment['role_code'])->first();
            if (! $role) {
                $this->command->warn("Role '{$assignment['role_code']}' not found — run RoleSeeder first.");

                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $assignment['email']],
                [
                    'name' => $assignment['name'],
                    'username' => $assignment['email'],
                    'user_type' => 'user',
                    // Same bcrypt hash as the existing demo users (password: 'password')
                    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    'status' => 'active',
                ]
            );
            $user->forceFill(['email_verified_at' => now()])->save();

            UserRole::firstOrCreate(
                ['user_id' => $user->id, 'role_id' => $role->id]
            );
            $count++;
        }

        $this->command->info("UserRoleSeeder: {$count} demo user-role assignment(s) seeded.");
    }
}
