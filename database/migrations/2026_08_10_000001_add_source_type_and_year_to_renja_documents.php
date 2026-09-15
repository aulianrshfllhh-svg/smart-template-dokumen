<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            $table->string('source_type')->default('template')->comment('source of document: template or upload_word');
            $table->year('year')->nullable()->comment('budget year of the RENJA document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'year']);
        });
    }
};
?>
