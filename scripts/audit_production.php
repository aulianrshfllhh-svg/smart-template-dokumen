<?php

require 'd:/erenja-app/vendor/autoload.php';

$app = require_once 'd:/erenja-app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterOpd;
use App\Models\User;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Models\RenjaSection;
use App\Models\RenjaTableUtama;
use App\Models\RenjaTableEval;
use Illuminate\Support\Facades\DB;

echo "====================================================\n";
echo "       STEP 11 — DATABASE PRODUCTION AUDIT\n";
echo "====================================================\n\n";

$errors = [];
$warnings = [];

/*
|--------------------------------------------------------------------------
| 1. MASTER OPD
|--------------------------------------------------------------------------
*/

echo "[1] MASTER OPD\n";

$opdCount = MasterOpd::count();

echo "  Master OPD Count : {$opdCount}\n";

if ($opdCount !== 71) {
    $warnings[] = "Master OPD count = {$opdCount}, expected 71.";
    echo "  WARNING: Expected 71 OPD.\n";
} else {
    echo "  PASS: 71 OPD tersedia.\n";
}

$duplicateKode = MasterOpd::select('kode_opd')
    ->groupBy('kode_opd')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('kode_opd');

echo "  Duplicate kode OPD: " . $duplicateKode->count() . "\n";

if ($duplicateKode->count() > 0) {
    $errors[] = "Duplicate kode OPD ditemukan.";
}

/*
|--------------------------------------------------------------------------
| 2. USERS
|--------------------------------------------------------------------------
*/

echo "\n[2] USERS & ROLES\n";

echo "  Users Count: " . User::count() . "\n";

$roles = User::groupBy('role')
    ->select('role', DB::raw('COUNT(*) as count'))
    ->pluck('count', 'role');

echo "  Users by Role: " . json_encode($roles) . "\n";

$allowedRoles = [
    'admin',
    'verifikator',
    'operator',
];

$invalidRoles = User::whereNotIn('role', $allowedRoles)->count();

echo "  Invalid Roles: {$invalidRoles}\n";

if ($invalidRoles > 0) {
    $errors[] = "{$invalidRoles} user memiliki role tidak dikenal.";
}

/*
|--------------------------------------------------------------------------
| 3. OPERATOR → OPD RELATION
|--------------------------------------------------------------------------
*/

$operatorsWithoutOpd = User::where('role', 'operator')
    ->whereNull('opd_id')
    ->count();

echo "  Operator without OPD: {$operatorsWithoutOpd}\n";

if ($operatorsWithoutOpd > 0) {
    $errors[] = "{$operatorsWithoutOpd} operator tidak memiliki OPD.";
}

$usersInvalidOpd = User::whereNotNull('opd_id')
    ->whereNotIn('opd_id', MasterOpd::pluck('id'))
    ->count();

echo "  Users with invalid OPD: {$usersInvalidOpd}\n";

if ($usersInvalidOpd > 0) {
    $errors[] = "{$usersInvalidOpd} user memiliki opd_id invalid.";
}

/*
|--------------------------------------------------------------------------
| 4. DOCUMENT TEMPLATES
|--------------------------------------------------------------------------
*/

echo "\n[3] DOCUMENT TEMPLATES\n";

$templates = DocumentTemplate::pluck('code')->toArray();

echo "  Template Count: " . count($templates) . "\n";
echo "  Templates: " . json_encode($templates) . "\n";

$requiredTemplates = [
    'RENJA_MURNI',
    'RENJA_PERUBAHAN',
    'RENJA_LAMPIRAN_MURNI',
    'RENJA_LAMPIRAN_PERUBAHAN',
];

foreach ($requiredTemplates as $code) {

    $count = DocumentTemplate::where('code', $code)->count();

    if ($count === 0) {
        $errors[] = "Template {$code} tidak ditemukan.";
        echo "  FAIL: {$code}\n";
    } elseif ($count > 1) {
        $errors[] = "Template {$code} duplicate.";
        echo "  FAIL: {$code} duplicate.\n";
    } else {

        $template = DocumentTemplate::where('code', $code)->first();

        if (!$template->is_active) {
            $errors[] = "Template {$code} tidak aktif.";
            echo "  FAIL: {$code} inactive.\n";
        } else {
            echo "  PASS: {$code}\n";
        }
    }
}

/*
|--------------------------------------------------------------------------
| 5. TEMPLATE SECTIONS
|--------------------------------------------------------------------------
*/

echo "\n[4] TEMPLATE SECTIONS\n";

echo "  Total Template Sections: " . TemplateSection::count() . "\n";

foreach (DocumentTemplate::all() as $template) {

    $count = $template->sections()->count();

    echo "  {$template->code}: {$count} sections\n";

    if ($count === 0) {
        $errors[] = "Template {$template->code} tidak memiliki section.";
    }

    $orphanTemplateSections = TemplateSection::where('template_id', $template->id)
        ->whereNotNull('parent_id')
        ->whereNotIn(
            'parent_id',
            TemplateSection::where('template_id', $template->id)->pluck('id')
        )
        ->count();

    if ($orphanTemplateSections > 0) {
        $errors[] = "Template {$template->code} memiliki orphan parent section.";
    }
}

/*
|--------------------------------------------------------------------------
| 6. RENJA DOCUMENT
|--------------------------------------------------------------------------
*/

echo "\n[5] RENJA DOCUMENTS\n";

echo "  Documents: " . RenjaDocument::count() . "\n";

$allowedTypes = [
    'RENJA_MURNI',
    'RENJA_PERUBAHAN',
    'RENJA_LAMPIRAN_MURNI',
    'RENJA_LAMPIRAN_PERUBAHAN',
];

$invalidTypes = RenjaDocument::whereNotIn(
    'jenis_dokumen',
    $allowedTypes
)->count();

echo "  Invalid document types: {$invalidTypes}\n";

if ($invalidTypes > 0) {
    $errors[] = "{$invalidTypes} dokumen memiliki jenis_dokumen invalid.";
}

/*
|--------------------------------------------------------------------------
| 7. DOCUMENT → OPD
|--------------------------------------------------------------------------
*/

$orphanDocs = RenjaDocument::whereNotIn(
    'opd_id',
    MasterOpd::pluck('id')
)->count();

echo "  Orphan Documents: {$orphanDocs}\n";

if ($orphanDocs > 0) {
    $errors[] = "{$orphanDocs} dokumen memiliki OPD invalid.";
}

/*
|--------------------------------------------------------------------------
| 8. DOCUMENT → TEMPLATE
|--------------------------------------------------------------------------
*/

$orphanTemplateDocs = RenjaDocument::whereNotIn(
    'template_id',
    DocumentTemplate::pluck('id')
)->count();

echo "  Documents with invalid template: {$orphanTemplateDocs}\n";

if ($orphanTemplateDocs > 0) {
    $errors[] = "{$orphanTemplateDocs} dokumen memiliki template invalid.";
}

/*
|--------------------------------------------------------------------------
| 9. YEAR
|--------------------------------------------------------------------------
*/

$invalidYears = RenjaDocument::whereNull('tahun_anggaran')
    ->orWhere('tahun_anggaran', '<', 2020)
    ->count();

echo "  Invalid/null tahun_anggaran: {$invalidYears}\n";

if ($invalidYears > 0) {
    $errors[] = "{$invalidYears} dokumen memiliki tahun_anggaran invalid.";
}

/*
|--------------------------------------------------------------------------
| 10. STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'draft',
    'submitted',
    'perlu_revisi',
    'dikirim_ulang',
    'disetujui',
    'menunggu_pemeriksaan',
    'sedang_diperiksa',
];

$invalidStatuses = RenjaDocument::whereNotIn(
    'status',
    $allowedStatuses
)->count();

echo "  Invalid statuses: {$invalidStatuses}\n";

if ($invalidStatuses > 0) {
    $errors[] = "{$invalidStatuses} dokumen memiliki status yang tidak dikenal.";
}

$statusSummary = RenjaDocument::groupBy('status')
    ->select('status', DB::raw('COUNT(*) as count'))
    ->pluck('count', 'status');

echo "  Status Distribution: "
    . json_encode($statusSummary)
    . "\n";

/*
|--------------------------------------------------------------------------
| 11. RENJA SECTIONS
|--------------------------------------------------------------------------
*/

echo "\n[6] RENJA SECTIONS\n";

echo "  Renja Sections: " . RenjaSection::count() . "\n";

$orphanSections = RenjaSection::whereNotIn(
    'document_id',
    RenjaDocument::pluck('id')
)->count();

echo "  Orphan Sections: {$orphanSections}\n";

if ($orphanSections > 0) {
    $errors[] = "{$orphanSections} orphan RenjaSection ditemukan.";
}

/*
|--------------------------------------------------------------------------
| 12. SECTION → TEMPLATE SECTION
|--------------------------------------------------------------------------
*/

$invalidTemplateSectionRefs = RenjaSection::whereNotNull('template_section_id')
    ->whereNotIn(
        'template_section_id',
        TemplateSection::pluck('id')
    )
    ->count();

echo "  Sections with invalid template_section_id: {$invalidTemplateSectionRefs}\n";

if ($invalidTemplateSectionRefs > 0) {
    $errors[] = "{$invalidTemplateSectionRefs} RenjaSection memiliki template_section_id invalid.";
}

/*
|--------------------------------------------------------------------------
| 13. TABLE UTAMA
|--------------------------------------------------------------------------
*/

echo "\n[7] TABLE UTAMA\n";

echo "  Table Utama: " . RenjaTableUtama::count() . "\n";

$orphanTableUtama = RenjaTableUtama::whereNotIn(
    'document_id',
    RenjaDocument::pluck('id')
)->count();

echo "  Orphan Table Utama: {$orphanTableUtama}\n";

if ($orphanTableUtama > 0) {
    $errors[] = "{$orphanTableUtama} orphan Table Utama.";
}

/*
|--------------------------------------------------------------------------
| 14. TABLE EVALUASI
|--------------------------------------------------------------------------
*/

echo "\n[8] TABLE EVALUASI\n";

echo "  Table Eval: " . RenjaTableEval::count() . "\n";

$orphanTableEval = RenjaTableEval::whereNotIn(
    'document_id',
    RenjaDocument::pluck('id')
)->count();

echo "  Orphan Table Eval: {$orphanTableEval}\n";

if ($orphanTableEval > 0) {
    $errors[] = "{$orphanTableEval} orphan Table Eval.";
}

/*
|--------------------------------------------------------------------------
| 15. LAMPIRAN RELATION
|--------------------------------------------------------------------------
*/

echo "\n[9] LAMPIRAN RELATION\n";

$lampiranDocs = RenjaDocument::whereIn(
    'jenis_dokumen',
    [
        'RENJA_LAMPIRAN_MURNI',
        'RENJA_LAMPIRAN_PERUBAHAN'
    ]
)->get();

echo "  Lampiran Documents: " . $lampiranDocs->count() . "\n";

foreach ($lampiranDocs as $lampiran) {

    $parent = $lampiran->getParentDocument();

    if (!$parent) {
        $errors[] = "Lampiran ID {$lampiran->id} tidak memiliki parent document.";
        echo "  FAIL: Lampiran {$lampiran->id} tidak memiliki parent.\n";
    } else {
        echo "  PASS: Lampiran {$lampiran->id} → Parent {$parent->id}\n";
    }
}

/*
|--------------------------------------------------------------------------
| 16. ROMAN NUMBER MASTER OPD
|--------------------------------------------------------------------------
*/

echo "\n[10] MASTER OPD LAMPIRAN NUMBER\n";

$nullRomanCount = MasterOpd::whereNull('nomor_lampiran_romawi')
    ->orWhere('nomor_lampiran_romawi', '')
    ->count();

echo "  Missing Roman Number: {$nullRomanCount}\n";

if ($nullRomanCount > 0) {
    $warnings[] = "{$nullRomanCount} OPD belum memiliki nomor Lampiran Romawi.";
}

/*
|--------------------------------------------------------------------------
| 17. DATABASE ENGINE
|--------------------------------------------------------------------------
*/

echo "\n[11] DATABASE ENGINE\n";

echo "  Connection: " . DB::connection()->getName() . "\n";
echo "  Driver: " . DB::connection()->getDriverName() . "\n";

/*
|--------------------------------------------------------------------------
| FINAL RESULT
|--------------------------------------------------------------------------
*/

echo "\n====================================================\n";
echo "                 FINAL AUDIT RESULT\n";
echo "====================================================\n";

echo "\nERRORS: " . count($errors) . "\n";

foreach ($errors as $error) {
    echo "  [FAIL] {$error}\n";
}

echo "\nWARNINGS: " . count($warnings) . "\n";

foreach ($warnings as $warning) {
    echo "  [WARNING] {$warning}\n";
}

echo "\n====================================================\n";

if (count($errors) === 0) {
    echo "DATABASE AUDIT: PASS\n";
} else {
    echo "DATABASE AUDIT: FAIL\n";
}

if (count($warnings) > 0) {
    echo "WARNING: Review warnings before production.\n";
}

echo "====================================================\n";
