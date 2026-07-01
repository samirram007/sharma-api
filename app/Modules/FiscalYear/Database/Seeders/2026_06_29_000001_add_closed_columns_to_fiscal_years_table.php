<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('assessment_year');
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['closed_at', 'closed_by']);
        });
    }
};
