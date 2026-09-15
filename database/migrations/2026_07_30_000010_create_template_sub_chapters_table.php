<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel: template_sub_chapters
     * Menyimpan daftar Sub-Bab yang terdeteksi dari dokumen acuan,
     * beserta tipe konten dan hasil fuzzy matching ke katalog tabel SIPD.
     */
    public function up(): void
    {
        Schema::create('template_sub_chapters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chapter_id')
                ->constrained('template_chapters')
                ->cascadeOnDelete();

            $table->string('kode');           // ex: "1.1", "2.3"
            $table->string('judul');          // ex: "Latar Belakang"

            // Tipe konten: rich_text (narasi) atau tabel (dari SIPD)
            $table->enum('tipe_konten', ['rich_text', 'tabel'])->default('rich_text');

            // Sumber data tabel jika tipe_konten = tabel (ex: "sipd_anggaran", "sipd_evaluasi")
            $table->string('sumber_data')->nullable();

            // Status matching ke katalog tabel
            // auto_matched: cocok dengan katalog → langsung pakai
            // perlu_review: tidak cocok atau anomali → admin perlu review manual
            // no_table: sub-bab ini bukan tabel (rich_text)
            $table->enum('match_status', ['auto_matched', 'perlu_review', 'no_table'])->default('no_table');

            // Skor kemiripan fuzzy matching (0–100)
            $table->unsignedTinyInteger('match_score')->default(0);

            $table->unsignedSmallInteger('urutan')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_sub_chapters');
    }
};
