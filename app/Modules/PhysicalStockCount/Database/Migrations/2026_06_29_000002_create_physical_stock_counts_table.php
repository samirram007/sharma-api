<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('godown_id')->constrained()->cascadeOnDelete();
            $table->date('count_date');
            $table->string('status')->default('draft'); // draft, verified, adjusted
            $table->foreignId('counted_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('physical_stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_stock_count_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('system_quantity', 14, 4)->default(0);
            $table->decimal('physical_quantity', 14, 4)->default(0);
            $table->decimal('rate', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->virtualAs('physical_quantity * rate');
            $table->decimal('difference', 14, 4)
                ->virtualAs('COALESCE(system_quantity, 0) - COALESCE(physical_quantity, 0)');
            $table->text('remarks')->nullable();
            $table->integer('entry_order')->default(0);
            $table->timestamps();

            $table->index(['physical_stock_count_id', 'stock_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_stock_count_items');
        Schema::dropIfExists('physical_stock_counts');
    }
};
