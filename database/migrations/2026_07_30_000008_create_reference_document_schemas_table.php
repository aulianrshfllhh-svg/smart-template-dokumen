<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel: reference_document_schemas
     * Menyimpan metadata file .docx acuan yang diupload admin,
     * beserta hasil JSON parsing dan status persetujuan.
     */
    public function up(): void
    {
        Schema::create('reference_document_schemas', function (Blueprint $table) {
            $table->id();

            // Metadata dokumen
            $table->string('jenis_dokumen')->default('Rencana Kerja (Renja)');
            $table->string('original_filename');          // Nama file asli saat upload
            $table->string('stored_path');                // Path relatif di storage

            // Status alur kerja: draft (baru diparse) → approved (siap generate) atau rejected
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');

            // Hasil parsing dari Python script (disimpan sebagai JSON)
            $table->longText('parsed_json')->nullable();
            $table->timestamp('parsed_at')->nullable();

            // Error message jika parsing gagal
            $table->text('parse_error_message')->nullable();

            // Audit trail approve/reject
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_document_schemas');
    }
};
