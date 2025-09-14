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
        // Enhance sales_orders table for trading operations
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->after('order_no');
            $table->date('expected_delivery_date')->nullable()->after('date');
            $table->date('actual_delivery_date')->nullable()->after('expected_delivery_date');
            $table->decimal('freight_cost', 15, 2)->default(0)->after('total_amount');
            $table->decimal('handling_cost', 15, 2)->default(0)->after('freight_cost');
            $table->decimal('insurance_cost', 15, 2)->default(0)->after('handling_cost');
            $table->decimal('total_cost', 15, 2)->default(0)->after('insurance_cost');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_cost');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('net_amount', 15, 2)->default(0)->after('discount_percentage');
            $table->text('notes')->nullable()->after('description');
            $table->text('terms_conditions')->nullable()->after('notes');
            $table->string('payment_terms')->nullable()->after('terms_conditions');
            $table->string('delivery_method')->nullable()->after('payment_terms');
            $table->string('approval_status')->default('pending')->after('status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('created_by')->nullable()->after('approved_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        // Enhance sales_order_lines table for inventory integration
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('account_id');
            $table->string('item_code')->nullable()->after('inventory_item_id');
            $table->string('item_name')->nullable()->after('item_code');
            $table->string('unit_of_measure')->nullable()->after('item_name');
            $table->decimal('delivered_qty', 15, 2)->default(0)->after('qty');
            $table->decimal('pending_qty', 15, 2)->default(0)->after('delivered_qty');
            $table->decimal('freight_cost', 15, 2)->default(0)->after('amount');
            $table->decimal('handling_cost', 15, 2)->default(0)->after('freight_cost');
            $table->decimal('total_cost', 15, 2)->default(0)->after('handling_cost');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_cost');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('net_amount', 15, 2)->default(0)->after('discount_percentage');
            $table->text('notes')->nullable()->after('tax_code_id');
            $table->string('status')->default('pending')->after('notes');
        });

        // Create sales_order_approvals table for workflow management
        Schema::create('sales_order_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('user_id');
            $table->string('approval_level');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Create customer_credit_limits table for credit management
        Schema::create('customer_credit_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('available_credit', 15, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->string('credit_status')->default('active'); // active, suspended, blocked
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // Create customer_pricing_tiers table for pricing management
        Schema::create('customer_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('tier_name');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('min_order_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // Create sales_commissions table for commission tracking
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('salesperson_id');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, approved, paid
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('salesperson_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Create customer_performance table for customer analysis
        Schema::create('customer_performance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->year('year');
            $table->integer('month');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('avg_order_value', 15, 2)->default(0);
            $table->decimal('payment_performance', 5, 2)->default(0); // percentage
            $table->decimal('profitability_rating', 3, 2)->default(0); // 0-5 scale
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->unique(['customer_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_performance');
        Schema::dropIfExists('sales_commissions');
        Schema::dropIfExists('customer_pricing_tiers');
        Schema::dropIfExists('customer_credit_limits');
        Schema::dropIfExists('sales_order_approvals');

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_item_id',
                'item_code',
                'item_name',
                'unit_of_measure',
                'delivered_qty',
                'pending_qty',
                'freight_cost',
                'handling_cost',
                'total_cost',
                'discount_amount',
                'discount_percentage',
                'net_amount',
                'notes',
                'status'
            ]);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn([
                'reference_no',
                'expected_delivery_date',
                'actual_delivery_date',
                'freight_cost',
                'handling_cost',
                'insurance_cost',
                'total_cost',
                'discount_amount',
                'discount_percentage',
                'net_amount',
                'notes',
                'terms_conditions',
                'payment_terms',
                'delivery_method',
                'approval_status',
                'approved_by',
                'approved_at',
                'created_by',
                'updated_by'
            ]);
        });
    }
};
