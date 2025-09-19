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
        Schema::table('tax_transactions', function (Blueprint $table) {
            // Add new columns for comprehensive tax tracking
            $table->string('transaction_no')->unique()->after('id');
            $table->string('tax_type')->after('transaction_type'); // ppn, pph_21, pph_22, pph_23, pph_26, pph_4_2
            $table->string('tax_category')->after('tax_type'); // input, output, withholding
            $table->unsignedBigInteger('business_partner_id')->nullable()->after('reference_id');
            $table->string('tax_number')->nullable()->after('business_partner_id'); // NPWP
            $table->string('tax_name')->nullable()->after('tax_number');
            $table->text('tax_address')->nullable()->after('tax_name');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_address');
            $table->decimal('total_amount', 15, 2)->default(0)->after('tax_amount');
            $table->date('due_date')->nullable()->after('status');
            $table->date('paid_date')->nullable()->after('due_date');
            $table->string('payment_method')->nullable()->after('paid_date');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->text('notes')->nullable()->after('payment_reference');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            // Rename existing column
            $table->renameColumn('base_amount', 'taxable_amount');

            // Add foreign keys
            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Add indexes
            $table->index(['transaction_date', 'tax_type']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_transactions', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['business_partner_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Drop indexes
            $table->dropIndex(['transaction_date', 'tax_type']);
            $table->dropIndex(['status', 'due_date']);

            // Rename column back
            $table->renameColumn('taxable_amount', 'base_amount');

            // Drop added columns
            $table->dropColumn([
                'transaction_no',
                'tax_type',
                'tax_category',
                'business_partner_id',
                'tax_number',
                'tax_name',
                'tax_address',
                'tax_rate',
                'total_amount',
                'due_date',
                'paid_date',
                'payment_method',
                'payment_reference',
                'notes',
                'created_by',
                'updated_by'
            ]);
        });
    }
};
