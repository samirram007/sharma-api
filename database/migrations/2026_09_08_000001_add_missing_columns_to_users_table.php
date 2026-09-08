<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The default 0001_01_01_000000_create_users_table migration (database/migrations)
 * created the users table without the columns the User module expects
 * (username, user_type, status, userable_*, provider_*). This migration
 * brings existing databases up to the module schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable();
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', ['admin', 'user'])->default('user');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active');
            }
            if (! Schema::hasColumn('users', 'userable_id')) {
                $table->unsignedBigInteger('userable_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'userable_type')) {
                $table->string('userable_type')->default('employee');
            }
            if (! Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable();
            }
            if (! Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id')->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'provider_token')) {
                $table->text('provider_token')->nullable();
            }
            if (! Schema::hasColumn('users', 'provider_refresh_token')) {
                $table->text('provider_refresh_token')->nullable();
            }

            if (! $this->hasUserableIndex()) {
                $table->index(['userable_id', 'userable_type']);
            }
        });

        // Backfill: rows created before the status column existed should be active.
        DB::table('users')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void
    {
        // Intentionally left empty — dropping columns would destroy data on
        // databases that already rely on them.
    }

    private function hasUserableIndex(): bool
    {
        $indexes = Schema::getIndexListing('users');

        return collect($indexes)->contains(fn ($i) => str_contains($i, 'userable'));
    }
};
