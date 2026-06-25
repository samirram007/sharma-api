<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'warning', 'error', 'info'
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('related_entity_type')->nullable(); // 'freight', 'voucher', etc.
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->string('field')->nullable(); // specific field name that's missing
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
