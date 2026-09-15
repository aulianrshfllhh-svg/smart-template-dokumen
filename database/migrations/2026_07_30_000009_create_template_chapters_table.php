<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel: template_chapters
     * Menyimpan daftar BAB yang terdeteksi dari dokumen acuan.
     */
    public function up(): void
    {
        Schema::create('template_chapters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schema_id')
                ->constrained('reference_document_schemas')
                ->cascadeOnDelete();

            $table->string('nomor_bab');      // ex: "I", "II", "III"
            $table->string('bab_code');       // ex: "BAB I", "BAB II" — konsisten dgn renja_sections
            $table->string('judul');          // ex: "PENDAHULUAN"
            $table->unsignedSmallInteger('urutan')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_chapters');
    }
};
