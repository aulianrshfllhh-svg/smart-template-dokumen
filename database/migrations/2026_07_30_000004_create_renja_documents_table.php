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
        Schema::create('renja_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('master_opd')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun_anggaran');
            $table->string('jenis_dokumen')->default('Rencana Kerja (Renja)');
            $table->enum('status', ['draft', 'submitted', 'revision', 'approved'])->default('draft');
            
            // Narasi Bab I - V
            $table->longText('latar_belakang')->nullable();
            $table->longText('landasan_hukum')->nullable();
            $table->longText('maksud_tujuan')->nullable();
            $table->longText('sistematika')->nullable();
            $table->longText('evaluasi_narasi')->nullable();
            $table->longText('isu_strategis_narasi')->nullable();
            $table->longText('tujuan_sasaran_narasi')->nullable();
            $table->longText('program_kegiatan_narasi')->nullable();
            $table->longText('penutup_narasi')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renja_documents');
    }
};
