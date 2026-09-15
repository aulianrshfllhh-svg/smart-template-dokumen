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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('username_nip')->unique()->after('id');
            $table->string('nama_lengkap')->after('username_nip');
            $table->enum('role', ['admin', 'verifikator', 'operator'])->default('operator')->after('password');
            $table->foreignId('opd_id')->nullable()->after('role')->constrained('master_opd')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn(['username_nip', 'nama_lengkap', 'role', 'opd_id']);
        });
    }
};
