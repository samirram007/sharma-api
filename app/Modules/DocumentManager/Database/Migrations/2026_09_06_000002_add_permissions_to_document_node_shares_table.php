<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_node_shares', function (Blueprint $table) {
            // CSV of granted actions: view,write,copy,move,delete,share.
            // 'view' is implicit (a share grants at least visibility), the
            // rest default to denied so sharing is read-only unless the
            // owner opts in per action.
            $table->string('permissions')->default('view')->after('target_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_node_shares', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
