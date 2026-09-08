<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voucher_classifications')) {
            return;
        }

        Schema::create('voucher_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('voucher_type_id')->nullable();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system_defined')->default(false);
            $table->boolean('requires_approval')->default(false);

            $table->index('company_id');
            $table->index('voucher_type_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_classifications');
    }
};
