<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Verification Workspace extensions.
     */
    public function up(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('renja_documents', 'assigned_verificator_id')) {
                $table->foreignId('assigned_verificator_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('renja_documents', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('renja_documents', 'priority_score')) {
                $table->integer('priority_score')->default(0);
            }
            if (!Schema::hasColumn('renja_documents', 'section_review_status')) {
                $table->json('section_review_status')->nullable();
            }
            if (!Schema::hasColumn('renja_documents', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (!Schema::hasColumn('renja_documents', 'revision_count')) {
                $table->integer('revision_count')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renja_documents', function (Blueprint $table) {
            $table->dropForeign(['assigned_verificator_id']);
            $table->dropForeign(['updated_by_user_id']);
            $table->dropColumn([
                'assigned_verificator_id',
                'updated_by_user_id',
                'priority_score',
                'section_review_status',
                'submitted_at',
                'revision_count',
            ]);
        });
    }
};
