<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom content_type ke renja_sections yang sudah ada.
     * Kolom ini memberi tahu editor apakah section berisi narasi (rich_text)
     * atau membutuhkan tabel SIPD (tabel).
     * Juga menambahkan source_schema_id untuk referensi dari mana section ini di-generate.
     */
    public function up(): void
    {
        Schema::table('renja_sections', function (Blueprint $table) {
            // Tipe konten section: rich_text (default, backward compatible) atau tabel
            $table->enum('content_type', ['rich_text', 'tabel'])
                ->default('rich_text')
                ->after('order_index');

            // Referensi ke schema yang men-generate section ini (nullable untuk section lama)
            $table->foreignId('source_schema_id')
                ->nullable()
                ->after('content_type')
                ->constrained('reference_document_schemas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_sections', function (Blueprint $table) {
            $table->dropForeign(['source_schema_id']);
            $table->dropColumn(['content_type', 'source_schema_id']);
        });
    }
};
