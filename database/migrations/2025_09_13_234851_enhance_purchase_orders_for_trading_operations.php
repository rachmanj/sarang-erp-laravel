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
        // Enhance purchase_orders table for trading operations
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->after('order_no');
            $table->date('expected_delivery_date')->nullable()->after('date');
            $table->date('actual_delivery_date')->nullable()->after('expected_delivery_date');
            $table->decimal('freight_cost', 15, 2)->default(0)->after('total_amount');
            $table->decimal('handling_cost', 15, 2)->default(0)->after('freight_cost');
            $table->decimal('insurance_cost', 15, 2)->default(0)->after('handling_cost');
            $table->decimal('total_cost', 15, 2)->default(0)->after('insurance_cost');
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

        // Enhance purchase_order_lines table for inventory integration
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('account_id');
            $table->string('item_code')->nullable()->after('inventory_item_id');
            $table->string('item_name')->nullable()->after('item_code');
            $table->string('unit_of_measure')->nullable()->after('item_name');
            $table->decimal('received_qty', 15, 2)->default(0)->after('qty');
            $table->decimal('pending_qty', 15, 2)->default(0)->after('received_qty');
            $table->decimal('freight_cost', 15, 2)->default(0)->after('amount');
            $table->decimal('handling_cost', 15, 2)->default(0)->after('freight_cost');
            $table->decimal('total_cost', 15, 2)->default(0)->after('handling_cost');
            $table->text('notes')->nullable()->after('tax_code_id');
            $table->string('status')->default('pending')->after('notes');
        });

        // Create purchase_order_approvals table for workflow management
        Schema::create('purchase_order_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('user_id');
            $table->string('approval_level');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Create supplier_performance table for supplier comparison
        Schema::create('supplier_performance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_partner_id');
            $table->year('year');
            $table->integer('month');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('avg_delivery_days', 5, 2)->default(0);
            $table->decimal('quality_rating', 3, 2)->default(0); // 0-5 scale
            $table->decimal('price_rating', 3, 2)->default(0); // 0-5 scale
            $table->decimal('service_rating', 3, 2)->default(0); // 0-5 scale
            $table->decimal('overall_rating', 3, 2)->default(0); // 0-5 scale
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('cascade');
            $table->unique(['business_partner_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance');
        Schema::dropIfExists('purchase_order_approvals');

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_item_id',
                'item_code',
                'item_name',
                'unit_of_measure',
                'received_qty',
                'pending_qty',
                'freight_cost',
                'handling_cost',
                'total_cost',
                'notes',
                'status'
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'reference_no',
                'expected_delivery_date',
                'actual_delivery_date',
                'freight_cost',
                'handling_cost',
                'insurance_cost',
                'total_cost',
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
