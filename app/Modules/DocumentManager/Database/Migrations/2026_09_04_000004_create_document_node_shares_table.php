<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_node_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_node_id')->constrained('document_nodes')->cascadeOnDelete();
            // Null target_type = shared with an individual user (target_id is a user id);
            // 'role' = shared with everyone holding a role.
            $table->string('target_type')->default('user'); // 'user' | 'role'
            $table->unsignedBigInteger('target_id');
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_node_id', 'target_type', 'target_id'], 'doc_share_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_node_shares');
    }
};
