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
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->date('disposal_date');
            $table->enum('disposal_type', ['sale', 'scrap', 'donation', 'trade_in', 'other']);
            $table->decimal('disposal_proceeds', 15, 2)->nullable(); // Sale price or scrap value
            $table->decimal('book_value_at_disposal', 15, 2); // Asset's book value when disposed
            $table->decimal('gain_loss_amount', 15, 2); // Calculated gain/loss
            $table->enum('gain_loss_type', ['gain', 'loss', 'neutral']); // Type of gain/loss
            $table->text('disposal_reason')->nullable();
            $table->string('disposal_method')->nullable(); // How it was disposed (auction, direct sale, etc.)
            $table->string('disposal_reference')->nullable(); // Reference number/document
            $table->foreignId('journal_id')->nullable()->constrained('journals'); // GL journal for disposal
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['asset_id', 'disposal_date']);
            $table->index(['disposal_date', 'status']);
            $table->index('journal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
