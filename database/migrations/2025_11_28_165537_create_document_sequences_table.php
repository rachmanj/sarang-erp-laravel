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
        if (!Schema::hasTable('document_sequences')) {
            Schema::create('document_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 50)->index();
                $table->string('year_month', 6)->index();
                $table->integer('last_sequence')->default(0);
                $table->timestamps();
                $table->unique(['document_type', 'year_month'], 'unique_type_month');
            });
        }

        Schema::table('document_sequences', function (Blueprint $table) {
            if (!Schema::hasColumn('document_sequences', 'company_entity_id')) {
                $table->foreignId('company_entity_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('company_entities')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('document_sequences', 'document_code')) {
                $table->string('document_code', 5)
                    ->nullable()
                    ->after('document_type');
            }

            if (!Schema::hasColumn('document_sequences', 'year')) {
                $table->unsignedSmallInteger('year')
                    ->nullable()
                    ->after('year_month');
            }

            if (!Schema::hasColumn('document_sequences', 'current_number')) {
                $table->unsignedInteger('current_number')
                    ->default(0)
                    ->after('last_sequence');
            }

            $table->unique(
                ['company_entity_id', 'document_code', 'year'],
                'doc_seq_entity_code_year_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            if (Schema::hasColumn('document_sequences', 'company_entity_id')) {
                $table->dropForeign(['company_entity_id']);
            }

            if (Schema::hasColumn('document_sequences', 'document_code')
                && Schema::hasColumn('document_sequences', 'year')) {
                $table->dropUnique('doc_seq_entity_code_year_unique');
            }

            if (Schema::hasColumn('document_sequences', 'current_number')) {
                $table->dropColumn('current_number');
            }

            if (Schema::hasColumn('document_sequences', 'year')) {
                $table->dropColumn('year');
            }

            if (Schema::hasColumn('document_sequences', 'document_code')) {
                $table->dropColumn('document_code');
            }

            if (Schema::hasColumn('document_sequences', 'company_entity_id')) {
                $table->dropColumn('company_entity_id');
            }
        });

        // Note: we intentionally keep the original document_sequences table intact
        // to avoid breaking existing numbering logic until the new format is fully adopted.
    }
};
