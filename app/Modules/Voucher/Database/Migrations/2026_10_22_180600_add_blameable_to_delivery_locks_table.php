<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every lock/unlock records who performed it (`created_by` on the lock,
 * `updated_by` on the unlock) so the Freight grid and the postpone dialog can
 * show "locked by …" / "released by …". Idempotent, because the very first
 * draft of the table may already carry these columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_locks')) {
            return;
        }

        Schema::table('delivery_locks', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_locks', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_locked');
            }

            if (! Schema::hasColumn('delivery_locks', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_locks')) {
            return;
        }

        Schema::table('delivery_locks', function (Blueprint $table) {
            $stale = array_values(array_filter(
                ['created_by', 'updated_by'],
                fn (string $column) => Schema::hasColumn('delivery_locks', $column)
            ));

            if ($stale !== []) {
                $table->dropColumn($stale);
            }
        });
    }
};
