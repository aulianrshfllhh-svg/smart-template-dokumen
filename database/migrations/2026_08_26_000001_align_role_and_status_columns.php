<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('operator')->change();
        });
        Schema::table('renja_documents', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Do not shrink constraints: existing workflow values must remain valid.
    }
};
