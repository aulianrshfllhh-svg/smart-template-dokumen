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
        Schema::create('renja_table_eval', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('renja_documents')->cascadeOnDelete();
            $table->enum('jenis_tabel', ['evaluasi_2.1', 'review_rkpd_2.4']);
            $table->string('kode_rekening');
            $table->string('nama_program_kegiatan');
            $table->text('indikator_kinerja');
            $table->string('target_capaian')->nullable();
            $table->decimal('pagu_indikatif', 15, 2)->default(0);
            $table->string('realisasi_capaian')->nullable();
            $table->decimal('realisasi_pagu', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renja_table_eval');
    }
};
