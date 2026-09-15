<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel: template_table_columns
     * Menyimpan definisi kolom untuk sub-bab bertipe tabel
     * yang terdeteksi dari dokumen acuan.
     */
    public function up(): void
    {
        Schema::create('template_table_columns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sub_chapter_id')
                ->constrained('template_sub_chapters')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('urutan');    // Urutan kolom (1, 2, 3, ...)
            $table->string('nama_kolom');              // Nama kolom dari header tabel .docx
            $table->string('kolom_key')->nullable();   // Machine-readable key (ex: "kode_rekening")

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_table_columns');
    }
};
