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
        Schema::table('renja_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('renja_documents', 'catatan_bapperida')) {
                $table->text('catatan_bapperida')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            if (Schema::hasColumn('renja_documents', 'catatan_bapperida')) {
                $table->dropColumn('catatan_bapperida');
            }
        });
    }
};
