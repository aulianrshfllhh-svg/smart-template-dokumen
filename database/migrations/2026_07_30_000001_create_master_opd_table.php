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
        Schema::create('master_opd', function (Blueprint $table) {
            $table->id();
            $table->string('kode_opd')->unique();
            $table->string('nama_opd');
            $table->integer('lampiran_number')->nullable()->unique();
            $table->string('nomor_lampiran_romawi')->default('LAMPIRAN I');
            $table->string('jenis_lampiran_default')->default('Lampiran IV');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_opd');
    }
};
