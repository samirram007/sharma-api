<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->enum('reason', ['vehicle_failure', 'uncertain_situation', 'party_unavailable', 'other'])->nullable();
            $table->text('note')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->timestamps();
            $table->blamable();

            $table->index('voucher_id', 'delivery_locks_voucher_id_index');
            $table->index('is_locked', 'delivery_locks_is_locked_index');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_locks');
    }
};
