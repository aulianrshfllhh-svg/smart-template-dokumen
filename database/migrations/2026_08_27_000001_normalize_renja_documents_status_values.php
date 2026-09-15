<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for data normalization of renja_documents status column.
     */
    public function up(): void
    {
        // 1. Normalisasi status legacy ke canonical disetujui
        DB::table('renja_documents')
            ->whereIn('status', ['approved'])
            ->update(['status' => 'disetujui']);

        // 2. Normalisasi status legacy ke canonical perlu_revisi
        DB::table('renja_documents')
            ->whereIn('status', ['revisi', 'revision'])
            ->update(['status' => 'perlu_revisi']);

        // 3. Normalisasi status legacy ke canonical sedang_diperiksa
        DB::table('renja_documents')
            ->whereIn('status', ['under_review', 'sedang_direview', 'review'])
            ->update(['status' => 'sedang_diperiksa']);

        // 4. Normalisasi status legacy ke canonical submitted
        DB::table('renja_documents')
            ->whereIn('status', ['menunggu_pemeriksaan', 'menunggu_verifikasi'])
            ->update(['status' => 'submitted']);

        // 5. Normalisasi status legacy ke canonical final
        DB::table('renja_documents')
            ->whereIn('status', ['dikunci'])
            ->update(['status' => 'final']);

        // 6. Normalisasi status legacy ke canonical draft
        DB::table('renja_documents')
            ->whereIn('status', ['belum_dikerjakan'])
            ->update(['status' => 'draft']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse migration required as normalization preserves canonical definitions.
    }
};
