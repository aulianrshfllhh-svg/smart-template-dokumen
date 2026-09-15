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
            if (!Schema::hasColumn('renja_documents', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->index();
            }
            if (!Schema::hasColumn('renja_documents', 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }
            if (!Schema::hasColumn('renja_documents', 'archived_by_user_id')) {
                $table->foreignId('archived_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('renja_documents', 'archive_notes')) {
                $table->text('archive_notes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            if (Schema::hasColumn('renja_documents', 'archived_by_user_id')) {
                $table->dropForeign(['archived_by_user_id']);
            }
            $table->dropColumn([
                'is_archived',
                'archived_at',
                'archived_by_user_id',
                'archive_notes',
            ]);
        });
    }
};
