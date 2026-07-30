<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update the main header table for ERP requirements
        Schema::table('voucher_classifications', function (Blueprint $table) {
            if (! Schema::hasColumn('voucher_classifications', 'company_id')) {
                $table->unsignedBigInteger('company_id')->after('id')->nullable();
            }
            if (! Schema::hasColumn('voucher_classifications', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->after('company_id')->nullable();
            }
            if (! Schema::hasColumn('voucher_classifications', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('status');
            }
            if (! Schema::hasColumn('voucher_classifications', 'is_system_defined')) {
                $table->boolean('is_system_defined')->default(false)->after('is_default');
            }
            if (! Schema::hasColumn('voucher_classifications', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('is_system_defined');
            }

            // Remove the temporary JSON fields from previous turn to enforce normalization
            $table->dropColumn(['inclusion_rules', 'exclusion_rules', 'default_value', 'percentage']);
        });

        // 1. Scope Rules (Filters)
        Schema::create('voucher_classification_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_classification_id');
            $table->string('filterable_type')->comment('AccountGroup or AccountLedger');
            $table->unsignedBigInteger('filterable_id');
            $table->enum('filter_type', ['include', 'exclude'])->default('include');
            $table->timestamps();

            $table->foreign('voucher_classification_id', 'vcf_vc_id_foreign')->references('id')->on('voucher_classifications')->onDelete('cascade');
        });

        // 2. Default Allocation Rules
        Schema::create('voucher_classification_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_classification_id');
            $table->unsignedBigInteger('ledger_id');
            $table->string('allocation_type')->comment('fixed_percentage, fixed_amount, user_defined');
            $table->decimal('value', 18, 4)->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_readonly')->default(false);
            $table->boolean('is_mandatory')->default(true);
            $table->string('rounding_method')->nullable(); // -- normal, upward, downward
            $table->decimal('rounding_limit', 18, 4)->nullable();
            $table->timestamps();

            $table->foreign('voucher_classification_id', 'vca_vc_id_foreign')->references('id')->on('voucher_classifications')->onDelete('cascade');
        });

        // 3. Tax and Statutory Rule Engine
        Schema::create('voucher_classification_tax_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_classification_id');
            $table->unsignedBigInteger('tax_ledger_id');
            $table->string('calculation_basis')->comment('on_total_sales, on_previous_total, on_item_rate');
            $table->decimal('percentage', 8, 4)->nullable();
            $table->boolean('is_override_allowed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('voucher_classification_id', 'vctr_vc_id_foreign')->references('id')->on('voucher_classifications')->onDelete('cascade');
        });

        // 4. Inventory Mapping Rules
        Schema::create('voucher_classification_inventory_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_classification_id');
            $table->unsignedBigInteger('item_group_id')->nullable();
            $table->unsignedBigInteger('stock_item_id')->nullable();
            $table->unsignedBigInteger('income_ledger_id')->nullable();
            $table->unsignedBigInteger('expense_ledger_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();

            $table->foreign('voucher_classification_id', 'vcim_vc_id_foreign')->references('id')->on('voucher_classifications')->onDelete('cascade');
        });

        // 5. UI & Dynamic Form Metadata
        Schema::create('voucher_classification_ui_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voucher_classification_id');
            $table->string('field_name'); // -- cost_center, narration, etc.
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_readonly')->default(false);
            $table->string('default_value_formula')->nullable();
            $table->timestamps();

            $table->foreign('voucher_classification_id', 'vcuic_vc_id_foreign')->references('id')->on('voucher_classifications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_classification_ui_configs');
        Schema::dropIfExists('voucher_classification_inventory_maps');
        Schema::dropIfExists('voucher_classification_tax_rules');
        Schema::dropIfExists('voucher_classification_allocations');
        Schema::dropIfExists('voucher_classification_filters');
    }
};
