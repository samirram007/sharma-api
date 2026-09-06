<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_nodes', function (Blueprint $table) {
            $table->id();
            // Folder or file name.
            $table->string('name');
            // 'folder' | 'file'
            $table->string('kind')->default('file')->index();
            // 'private' (owner only) | 'protected' (owner + explicit shares) |
            // 'public' (any signed-in user of this company)
            $table->string('visibility')->default('private')->index();
            $table->foreignId('parent_id')->nullable()->constrained('document_nodes')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users');
            // Classification: category + document type.
            $table->foreignId('category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('description')->nullable();

            // File payload (files only).
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'visibility']);
            $table->index(['parent_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_nodes');
    }
};
