<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cost allocation methods table
        Schema::create('cost_allocation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cost categories table
        Schema::create('cost_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['direct', 'indirect', 'overhead']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cost allocations table - tracks how costs are allocated to products/customers
        Schema::create('cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('allocation_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('allocation_method_id')->constrained('cost_allocation_methods')->onDelete('cascade');
            $table->decimal('allocation_rate', 8, 4)->default(0); // Percentage or fixed rate
            $table->enum('allocation_base', ['quantity', 'value', 'weight', 'volume', 'fixed']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Cost history table - tracks all cost transactions
        Schema::create('cost_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->enum('transaction_type', ['purchase', 'freight', 'handling', 'overhead', 'adjustment']);
            $table->foreignId('inventory_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sales_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('allocated_cost', 15, 4)->default(0);
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_date']);
            $table->index(['inventory_item_id', 'transaction_date']);
        });

        // Product cost summaries table - aggregated cost data per product
        Schema::create('product_cost_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_purchase_cost', 15, 4)->default(0);
            $table->decimal('total_freight_cost', 15, 4)->default(0);
            $table->decimal('total_handling_cost', 15, 4)->default(0);
            $table->decimal('total_overhead_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('average_unit_cost', 15, 4)->default(0);
            $table->decimal('total_quantity', 15, 4)->default(0);
            $table->enum('valuation_method', ['fifo', 'lifo', 'weighted_average']);
            $table->timestamps();

            $table->unique(['inventory_item_id', 'period_start', 'period_end'], 'pcs_item_period_unique');
            $table->index(['period_start', 'period_end']);
        });

        // Customer cost allocations table - tracks costs allocated to customers
        Schema::create('customer_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('cost_allocation_id')->constrained()->onDelete('cascade');
            $table->date('allocation_date');
            $table->decimal('allocated_amount', 15, 4)->default(0);
            $table->decimal('allocation_percentage', 8, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['customer_id', 'allocation_date']);
        });

        // Margin analysis table - stores calculated margin data
        Schema::create('margin_analyses', function (Blueprint $table) {
            $table->id();
            $table->enum('analysis_type', ['product', 'customer', 'supplier', 'period']);
            $table->foreignId('inventory_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('vendors')->onDelete('cascade');
            $table->date('analysis_date');
            $table->decimal('revenue', 15, 4)->default(0);
            $table->decimal('cost_of_goods_sold', 15, 4)->default(0);
            $table->decimal('gross_margin', 15, 4)->default(0);
            $table->decimal('gross_margin_percentage', 8, 4)->default(0);
            $table->decimal('operating_expenses', 15, 4)->default(0);
            $table->decimal('net_margin', 15, 4)->default(0);
            $table->decimal('net_margin_percentage', 8, 4)->default(0);
            $table->decimal('quantity_sold', 15, 4)->default(0);
            $table->decimal('average_selling_price', 15, 4)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->timestamps();

            $table->index(['analysis_type', 'analysis_date']);
            $table->index(['inventory_item_id', 'analysis_date']);
            $table->index(['customer_id', 'analysis_date']);
        });

        // Supplier cost analysis table
        Schema::create('supplier_cost_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('vendors')->onDelete('cascade');
            $table->date('analysis_date');
            $table->decimal('total_purchase_value', 15, 4)->default(0);
            $table->decimal('total_freight_cost', 15, 4)->default(0);
            $table->decimal('total_handling_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('average_cost_per_unit', 15, 4)->default(0);
            $table->decimal('delivery_performance_score', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->decimal('cost_efficiency_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->timestamps();

            $table->index(['supplier_id', 'analysis_date']);
        });

        // Cost allocation rules table - defines how costs are allocated
        Schema::create('cost_allocation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->text('description')->nullable();
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->enum('allocation_method', ['percentage', 'fixed_amount', 'proportional']);
            $table->json('allocation_criteria'); // JSON for flexible criteria
            $table->decimal('allocation_value', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_allocation_rules');
        Schema::dropIfExists('supplier_cost_analyses');
        Schema::dropIfExists('margin_analyses');
        Schema::dropIfExists('customer_cost_allocations');
        Schema::dropIfExists('product_cost_summaries');
        Schema::dropIfExists('cost_histories');
        Schema::dropIfExists('cost_allocations');
        Schema::dropIfExists('cost_categories');
        Schema::dropIfExists('cost_allocation_methods');
    }
};
