<?php

namespace App\Modules\Status\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Status\Models\Status;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        Status::create([
            'name' => 'Active',
            'code' => 'ACTIVE',
            'description' => 'Active status',
            'color' => '#22c55e',
            'status' => 'active',
        ]);

        Status::create([
            'name' => 'Inactive',
            'code' => 'INACTIVE',
            'description' => 'Inactive status',
            'color' => '#ef4444',
            'status' => 'active',
        ]);

        Status::create([
            'name' => 'Pending',
            'code' => 'PENDING',
            'description' => 'Pending approval status',
            'color' => '#f59e0b',
            'status' => 'active',
        ]);

        Status::create([
            'name' => 'Completed',
            'code' => 'COMPLETED',
            'description' => 'Completed status',
            'color' => '#3b82f6',
            'status' => 'active',
        ]);
    }
}
