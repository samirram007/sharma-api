<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The DocumentNode model uses the Blameable trait, which writes created_by /
 * updated_by on every insert/update — but the original table migration never
 * created those columns, so every INSERT failed with "Unknown column
 * 'created_by'".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_nodes', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('size_bytes')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
