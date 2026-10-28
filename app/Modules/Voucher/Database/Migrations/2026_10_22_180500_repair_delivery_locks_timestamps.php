<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs a `delivery_locks` table created by an earlier draft of
 * 2026_10_22_173859_create_delivery_locks_table, which shipped blameable
 * `created_by` / `updated_by` columns but no `created_at` / `updated_at`.
 *
 * Eloquent's default timestamps need those two columns; without them every
 * insert fails with "Unknown column 'updated_at' in 'INSERT INTO'".
 * Idempotent: safe on an already-correct table and on a fresh install.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_locks')) {
            return;
        }

        Schema::table('delivery_locks', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_locks', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('delivery_locks', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // The original migration owns the table's shape; nothing to undo here.
    }
};
