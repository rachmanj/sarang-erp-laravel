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
        // Add is_transit field to warehouses table
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_transit')->default(false)->after('is_active');
        });

        // Add transfer status and reference fields to inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->enum('transfer_status', ['pending', 'in_transit', 'received', 'completed', 'cancelled'])
                  ->default('pending')
                  ->after('reference_id');
            $table->unsignedBigInteger('transfer_out_id')->nullable()->after('transfer_status');
            $table->unsignedBigInteger('transfer_in_id')->nullable()->after('transfer_out_id');
            $table->text('transfer_notes')->nullable()->after('transfer_in_id');
            $table->timestamp('transit_date')->nullable()->after('transfer_notes');
            $table->timestamp('received_date')->nullable()->after('transit_date');
            
            // Add indexes for better performance
            $table->index('transfer_status');
            $table->index('transfer_out_id');
            $table->index('transfer_in_id');
        });

        // Create virtual transit warehouses for each existing physical warehouse
        $physicalWarehouses = DB::table('warehouses')->where('is_transit', false)->get();
        
        foreach ($physicalWarehouses as $warehouse) {
            DB::table('warehouses')->insert([
                'code' => $warehouse->code . '_TRANSIT',
                'name' => $warehouse->name . ' - Transit',
                'address' => 'Virtual Transit Location - Items in Transportation from ' . $warehouse->name,
                'contact_person' => $warehouse->contact_person,
                'phone' => $warehouse->phone,
                'email' => $warehouse->email,
                'is_active' => true,
                'is_transit' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove virtual transit warehouses
        DB::table('warehouses')->where('is_transit', true)->delete();

        // Remove added fields from inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['transfer_status']);
            $table->dropIndex(['transfer_out_id']);
            $table->dropIndex(['transfer_in_id']);
            $table->dropColumn([
                'transfer_status',
                'transfer_out_id',
                'transfer_in_id',
                'transfer_notes',
                'transit_date',
                'received_date'
            ]);
        });

        // Remove is_transit field from warehouses table
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('is_transit');
        });
    }
};