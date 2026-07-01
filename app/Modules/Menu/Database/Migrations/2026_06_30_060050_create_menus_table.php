<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_module_feature_id');
            $table->string('menu_name');
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->boolean('is_visible')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('app_module_feature_id')
                ->references('id')
                ->on('app_module_features')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('menu')
                ->onDelete('set null');

            $table->index(['app_module_feature_id']);
            $table->index(['parent_id']);
            $table->index(['sort_order']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
