<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shortcut nodes (kind = 'shortcut') point at another node instead of
 * carrying file payload. target_id is nulled when the target is deleted —
 * the surviving shortcut then renders as a broken link until removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_nodes', function (Blueprint $table) {
            $table->foreignId('target_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('document_nodes')
                ->nullOnDelete();
            $table->index('target_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_id');
        });
    }
};
