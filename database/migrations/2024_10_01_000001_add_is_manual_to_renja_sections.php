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
        if (Schema::hasTable('renja_sections') && !Schema::hasColumn('renja_sections', 'is_manual')) {
            Schema::table('renja_sections', function (Blueprint $table) {
                $table->boolean('is_manual')->default(false)->after('order_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('renja_sections') && Schema::hasColumn('renja_sections', 'is_manual')) {
            Schema::table('renja_sections', function (Blueprint $table) {
                $table->dropColumn('is_manual');
            });
        }
    }
};
