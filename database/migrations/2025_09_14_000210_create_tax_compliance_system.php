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
        // Skip tax_transactions table creation as it already exists
        // The table will be enhanced by a separate migration

        // Skip tax_codes table creation as it already exists

        // Create tax_periods table for tax reporting periods
        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->integer('month'); // 1-12
            $table->string('period_type')->default('monthly'); // monthly, quarterly, annual
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open'); // open, closed, locked
            $table->date('closing_date')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['year', 'month', 'period_type']);
        });

        // Create tax_reports table for tax reporting
        Schema::create('tax_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_period_id');
            $table->string('report_type'); // spt_ppn, spt_pph_21, spt_pph_22, etc.
            $table->string('report_name'); // Report description
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->date('submission_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('reference_number')->nullable(); // Tax office reference
            $table->json('report_data')->nullable(); // Report data in JSON format
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamps();

            $table->foreign('tax_period_id')->references('id')->on('tax_periods')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });

        // Create tax_settings table for tax configuration
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_name');
            $table->text('setting_value');
            $table->string('data_type')->default('string'); // string, number, boolean, json
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create tax_compliance_logs table for audit trail
        Schema::create('tax_compliance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // created, updated, deleted, submitted, approved, etc.
            $table->string('entity_type'); // tax_transaction, tax_report, etc.
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Create tax_calendar table for tax deadlines
        Schema::create('tax_calendar', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('event_type'); // deadline, reminder, holiday
            $table->date('event_date');
            $table->string('tax_type'); // ppn, pph_21, pph_22, etc.
            $table->string('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // monthly, quarterly, annual
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calendar');
        Schema::dropIfExists('tax_compliance_logs');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('tax_reports');
        Schema::dropIfExists('tax_periods');
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('tax_transactions');
    }
};
