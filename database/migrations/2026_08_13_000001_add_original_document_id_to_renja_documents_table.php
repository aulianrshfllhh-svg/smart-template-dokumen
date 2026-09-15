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
        Schema::table('renja_documents', function (Blueprint $table) {
            $table->foreignId('original_document_id')
                ->nullable()
                ->after('template_id')
                ->constrained('renja_documents')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            $table->dropForeign(['original_document_id']);
            $table->dropColumn('original_document_id');
        });
    }
};
