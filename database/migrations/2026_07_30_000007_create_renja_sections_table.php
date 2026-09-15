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
        Schema::create('renja_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('renja_documents')->cascadeOnDelete();
            $table->string('bab_code'); // ex: 'BAB I', 'BAB VI'
            $table->string('bab_title'); // ex: 'Pendahuluan', 'Penutup'
            $table->string('sub_bab_code'); // ex: '1.1', '6.1'
            $table->string('sub_bab_title'); // ex: 'Kesimpulan & Saran Penutup'
            $table->longText('content')->nullable();
            $table->text('guidance_text')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renja_sections');
    }
};
