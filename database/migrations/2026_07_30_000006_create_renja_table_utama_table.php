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
        Schema::create('renja_table_utama', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('renja_documents')->cascadeOnDelete();
            $table->string('kode_rekening');
            $table->string('nama_program_kegiatan');
            $table->text('uraian');
            $table->text('indikator');
            $table->string('lokasi')->nullable();
            $table->string('target_2027')->nullable();
            $table->decimal('pagu_2027', 15, 2)->default(0);
            $table->string('prakiraan_maju_target_2028')->nullable();
            $table->decimal('prakiraan_maju_pagu_2028', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renja_table_utama');
    }
};
