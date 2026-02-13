<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE delivery_orders MODIFY COLUMN status ENUM('draft', 'picking', 'packed', 'ready', 'in_transit', 'partial_delivered', 'delivered', 'completed', 'cancelled') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE delivery_orders MODIFY COLUMN status ENUM('draft', 'picking', 'packed', 'ready', 'in_transit', 'delivered', 'completed', 'cancelled') DEFAULT 'draft'");
    }
};
