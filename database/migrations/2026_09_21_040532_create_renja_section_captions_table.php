<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel caption untuk tabel dan gambar yang di-paste ke area isi sub-bab.
     * Digunakan untuk generate Daftar Tabel dan Daftar Gambar otomatis.
     */
    public function up(): void
    {
        Schema::create('renja_section_captions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->string('element_type', 20)->comment('table atau figure');
            $table->string('element_id', 100)->comment('ID unik elemen di dalam konten HTML section (data-elem-id)');
            $table->text('caption')->comment('Judul tabel/gambar, e.g. Tabel 3.1 Rencana Kerja OPD');
            $table->string('display_number', 30)->nullable()->comment('Nomor urut tampilan, e.g. Tabel 3.1 atau Gambar 2.1');
            $table->unsignedSmallInteger('order_index')->default(0)->comment('Urutan kemunculan dalam dokumen');
            $table->timestamps();

            $table->foreign('section_id')
                ->references('id')
                ->on('renja_sections')
                ->onDelete('cascade');

            $table->index(['section_id', 'element_type'], 'idx_captions_section_type');
            $table->unique(['section_id', 'element_id'], 'unique_section_element');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renja_section_captions');
    }
};
