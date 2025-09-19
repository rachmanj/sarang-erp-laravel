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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number')->unique();
            $table->foreignId('sales_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_partner_id')->constrained('business_partners')->onDelete('cascade');
            $table->text('delivery_address');
            $table->string('delivery_contact_person')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->date('planned_delivery_date');
            $table->date('actual_delivery_date')->nullable();
            $table->enum('delivery_method', ['pickup', 'courier', 'own_fleet', 'customer_pickup'])->default('own_fleet');
            $table->text('delivery_instructions')->nullable();
            $table->decimal('logistics_cost', 15, 2)->default(0);
            $table->enum('status', ['draft', 'picking', 'packed', 'ready', 'in_transit', 'delivered', 'completed', 'cancelled'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'planned_delivery_date']);
            $table->index(['business_partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
