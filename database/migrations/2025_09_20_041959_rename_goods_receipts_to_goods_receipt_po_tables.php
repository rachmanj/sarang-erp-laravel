<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename goods_receipts table to goods_receipt_po
        Schema::rename('goods_receipts', 'goods_receipt_po');
        
        // Rename goods_receipt_lines table to goods_receipt_po_lines
        Schema::rename('goods_receipt_lines', 'goods_receipt_po_lines');
        
        // Update foreign key reference in goods_receipt_po_lines table
        Schema::table('goods_receipt_po_lines', function (Blueprint $table) {
            // Check if foreign key exists before dropping
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'goods_receipt_po_lines' 
                AND CONSTRAINT_NAME LIKE '%grn_id%'
            ");
            
            if (!empty($foreignKeys)) {
                $table->dropForeign(['grn_id']);
            }
            $table->renameColumn('grn_id', 'grpo_id');
        });
        
        // Re-add foreign key constraint with new column name
        Schema::table('goods_receipt_po_lines', function (Blueprint $table) {
            $table->foreign('grpo_id')->references('id')->on('goods_receipt_po')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint
        Schema::table('goods_receipt_po_lines', function (Blueprint $table) {
            $table->dropForeign(['grpo_id']);
            $table->renameColumn('grpo_id', 'grn_id');
        });

        // Re-add original foreign key constraint
        Schema::table('goods_receipt_po_lines', function (Blueprint $table) {
            $table->foreign('grn_id')->references('id')->on('goods_receipts')->onDelete('cascade');
        });

        // Rename tables back to original names
        Schema::rename('goods_receipt_po_lines', 'goods_receipt_lines');
        Schema::rename('goods_receipt_po', 'goods_receipts');
    }
};
