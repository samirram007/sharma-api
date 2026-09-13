<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional folder accent colour (a hex string like "#3b82f6") applied to the
 * folder icon in the document panel; null falls back to the default amber.
 * Only folders use it — files/shortcuts leave it null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_nodes', function (Blueprint $table) {
            $table->string('color')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('document_nodes', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
