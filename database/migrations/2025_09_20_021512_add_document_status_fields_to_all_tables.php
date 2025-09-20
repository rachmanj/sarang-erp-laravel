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
        // Purchase Workflow Tables
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'po_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'po_closed_by_idx');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'grpo_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'grpo_closed_by_idx');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'pi_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'pi_closed_by_idx');
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'pp_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'pp_closed_by_idx');
        });

        // Sales Workflow Tables
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'so_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'so_closed_by_idx');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'do_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'do_closed_by_idx');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'si_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'si_closed_by_idx');
        });

        Schema::table('sales_receipts', function (Blueprint $table) {
            $table->enum('closure_status', ['open', 'closed'])->default('open')->after('status');
            $table->string('closed_by_document_type', 50)->nullable()->after('closure_status');
            $table->unsignedBigInteger('closed_by_document_id')->nullable()->after('closed_by_document_type');
            $table->timestamp('closed_at')->nullable()->after('closed_by_document_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable()->after('closed_at');

            $table->index(['closure_status', 'created_at'], 'sr_closure_status_idx');
            $table->index(['closed_by_document_type', 'closed_by_document_id'], 'sr_closed_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Purchase Workflow Tables
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_closure_status_idx');
            $table->dropIndex('po_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropIndex('grpo_closure_status_idx');
            $table->dropIndex('grpo_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex('pi_closure_status_idx');
            $table->dropIndex('pi_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropIndex('pp_closure_status_idx');
            $table->dropIndex('pp_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        // Sales Workflow Tables
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex('so_closure_status_idx');
            $table->dropIndex('so_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropIndex('do_closure_status_idx');
            $table->dropIndex('do_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('si_closure_status_idx');
            $table->dropIndex('si_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });

        Schema::table('sales_receipts', function (Blueprint $table) {
            $table->dropIndex('sr_closure_status_idx');
            $table->dropIndex('sr_closed_by_idx');
            $table->dropColumn(['closure_status', 'closed_by_document_type', 'closed_by_document_id', 'closed_at', 'closed_by_user_id']);
        });
    }
};
