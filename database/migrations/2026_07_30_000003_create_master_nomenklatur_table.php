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
        Schema::create('master_nomenklatur', function (Blueprint $table) {
            $table->id();
            $table->string('kode_rekening');
            $table->string('nama_nomenklatur');
            $table->enum('level', ['program', 'kegiatan', 'subkegiatan']);
            $table->unsignedSmallInteger('tahun_anggaran');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_nomenklatur');
    }
};
