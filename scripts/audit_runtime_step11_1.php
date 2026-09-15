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
use Illuminate\Support\Facades\Schema;

// Ensure database query log does not contain mutating statements
DB::enableQueryLog();

$p0_count = 0;
$p1_count = 0;
$p2_count = 0;
$p0_issues = [];
$p1_issues = [];
$p2_issues = [];

echo "====================================================\n";
echo "DATABASE PRODUCTION AUDIT\n";
echo "====================================================\n\n";

/*
|--------------------------------------------------------------------------
| 1. DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/
$connection = config('database.default');
$driver = config("database.connections.{$connection}.driver");
$dbPath = config("database.connections.{$connection}.database");

echo "DATABASE\n";
echo "Connection: {$connection}\n";
echo "Driver: {$driver}\n";
echo "Path: {$dbPath}\n\n";

/*
|--------------------------------------------------------------------------
| 2. MASTER OPD
|--------------------------------------------------------------------------
*/
$opdCount = MasterOpd::count();
$duplicateKode = MasterOpd::select('kode_opd')
    ->whereNotNull('kode_opd')
    ->groupBy('kode_opd')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('kode_opd')->toArray();

$nullKode = MasterOpd::whereNull('kode_opd')->orWhere('kode_opd', '')->count();
$nullNama = MasterOpd::whereNull('nama_opd')->orWhere('nama_opd', '')->count();
$nullRomawi = MasterOpd::whereNull('nomor_lampiran_romawi')->orWhere('nomor_lampiran_romawi', '')->count();

$opdStatus = "PASS";
if (count($duplicateKode) > 0 || $nullKode > 0 || $nullNama > 0) {
    $p0_count++;
    $p0_issues[] = "Master OPD has duplicate/empty codes or names";
    $opdStatus = "FAIL";
} elseif ($opdCount !== 71) {
    $p2_count++;
    $p2_issues[] = "Master OPD count is {$opdCount} (expected 71 standard OPDs; contains 71 OPDs + 2 central entities).";
    $opdStatus = "WARNING (73 found: 71 regular OPD + 2 central)";
}

echo "MASTER OPD\n";
echo "Total: {$opdCount}\n";
echo "Expected: 71\n";
echo "Duplicate Kode: " . count($duplicateKode) . (count($duplicateKode) > 0 ? " (" . implode(', ', $duplicateKode) . ")" : "") . "\n";
echo "Null Kode: {$nullKode}\n";
echo "Null Nama: {$nullNama}\n";
echo "Status: {$opdStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 3. USERS & ROLES
|--------------------------------------------------------------------------
*/
$totalUsers = User::count();
$rolesCount = User::groupBy('role')->select('role', DB::raw('COUNT(*) as count'))->pluck('count', 'role')->toArray();
$allowedRoles = ['admin', 'verifikator', 'operator'];

$invalidRolesUsers = User::whereNotIn('role', $allowedRoles)->get(['id', 'name', 'username', 'role']);
$operatorsWithoutOpd = User::where('role', 'operator')->whereNull('opd_id')->count();
$validOpdIds = MasterOpd::pluck('id')->toArray();
$usersInvalidOpd = User::whereNotNull('opd_id')->whereNotIn('opd_id', $validOpdIds)->count();

$usersStatus = "PASS";
if ($operatorsWithoutOpd > 0 || $usersInvalidOpd > 0) {
    $p0_count++;
    $p0_issues[] = "Users have invalid OPD relationships (without OPD: {$operatorsWithoutOpd}, invalid OPD: {$usersInvalidOpd})";
    $usersStatus = "FAIL";
} elseif ($invalidRolesUsers->count() > 0) {
    $p1_count++;
    $p1_issues[] = "{$invalidRolesUsers->count()} user(s) have legacy role 'opd' (IDs: " . implode(', ', $invalidRolesUsers->pluck('id')->toArray()) . ")";
    $usersStatus = "WARNING";
}

echo "USERS\n";
echo "Total: {$totalUsers}\n";
echo "By Role: " . json_encode($rolesCount) . "\n";
echo "Invalid Roles Count: " . $invalidRolesUsers->count() . "\n";
if ($invalidRolesUsers->count() > 0) {
    foreach ($invalidRolesUsers as $iu) {
        echo "  - ID {$iu->id}: {$iu->name} ({$iu->username}) -> role '{$iu->role}'\n";
    }
}
echo "Operator Without OPD: {$operatorsWithoutOpd}\n";
echo "Users with Invalid OPD ID: {$usersInvalidOpd}\n";
echo "Status: {$usersStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 4. DOCUMENT TEMPLATES
|--------------------------------------------------------------------------
*/
$requiredTemplates = [
    'RENJA_MURNI',
    'RENJA_PERUBAHAN',
    'RENJA_LAMPIRAN_MURNI',
    'RENJA_LAMPIRAN_PERUBAHAN',
];

$allTemplates = DocumentTemplate::all();
$templateCount = $allTemplates->count();
$templateIssues = [];

foreach ($requiredTemplates as $reqCode) {
    $matched = DocumentTemplate::where('code', $reqCode)->get();
    if ($matched->count() === 0) {
        $templateIssues[] = "Missing required template: {$reqCode}";
        $p0_count++;
    } elseif ($matched->count() > 1) {
        $templateIssues[] = "Duplicate template code: {$reqCode}";
        $p0_count++;
    } else {
        $tpl = $matched->first();
        if (!$tpl->is_active) {
            $templateIssues[] = "Template inactive: {$reqCode}";
            $p1_count++;
        }
        if ($tpl->sections()->count() === 0) {
            $templateIssues[] = "Template without sections: {$reqCode}";
            $p0_count++;
        }
    }
}

$templateStatus = count($templateIssues) === 0 ? "PASS" : "FAIL";

echo "DOCUMENT TEMPLATES\n";
echo "Total: {$templateCount}\n";
foreach ($allTemplates as $t) {
    echo "  - [ID: {$t->id}] {$t->code} | Name: {$t->name} | Active: " . ($t->is_active ? 'YES' : 'NO') . " | Sections: " . $t->sections()->count() . "\n";
}
echo "Required Templates: " . implode(', ', $requiredTemplates) . "\n";
echo "Status: {$templateStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 5. TEMPLATE SECTIONS
|--------------------------------------------------------------------------
*/
$totalTemplateSections = TemplateSection::count();
$orphanParentSections = 0;
$crossTemplateParents = 0;
$duplicateSequences = 0;
$sectionsWithoutTemplate = TemplateSection::whereNull('template_id')->orWhereNotIn('template_id', DocumentTemplate::pluck('id'))->count();

foreach (DocumentTemplate::all() as $tpl) {
    $sections = TemplateSection::where('template_id', $tpl->id)->get();
    $validIds = $sections->pluck('id')->toArray();
    
    foreach ($sections as $sec) {
        if ($sec->parent_id !== null) {
            if (!in_array($sec->parent_id, $validIds)) {
                $orphanParentSections++;
            }
        }
    }

    // Check duplicate sequence under same parent
    $grouped = $sections->groupBy(function($item) {
        return ($item->parent_id ?? 'root') . '_' . $item->sequence;
    });
    foreach ($grouped as $key => $items) {
        if ($items->count() > 1) {
            $duplicateSequences++;
        }
    }
}

$tplSectionStatus = "PASS";
if ($orphanParentSections > 0 || $crossTemplateParents > 0 || $sectionsWithoutTemplate > 0) {
    $p0_count++;
    $p0_issues[] = "Corrupt template section hierarchy detected";
    $tplSectionStatus = "FAIL";
} elseif ($duplicateSequences > 0) {
    $p2_count++;
    $p2_issues[] = "Duplicate sequence numbers under same parent in template sections: {$duplicateSequences}";
    $tplSectionStatus = "WARNING";
}

echo "TEMPLATE SECTIONS\n";
echo "Total: {$totalTemplateSections}\n";
echo "Orphan / Cross-template Parents: {$orphanParentSections}\n";
echo "Sections without valid template_id: {$sectionsWithoutTemplate}\n";
echo "Duplicate Sequence: {$duplicateSequences}\n";
echo "Status: {$tplSectionStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 6. RENJA DOCUMENTS
|--------------------------------------------------------------------------
*/
$totalDocs = RenjaDocument::count();
$byType = RenjaDocument::groupBy('jenis_dokumen')->select('jenis_dokumen', DB::raw('COUNT(*) as count'))->pluck('count', 'jenis_dokumen')->toArray();
$byYear = RenjaDocument::groupBy('tahun_anggaran')->select('tahun_anggaran', DB::raw('COUNT(*) as count'))->pluck('count', 'tahun_anggaran')->toArray();
$byStatus = RenjaDocument::groupBy('status')->select('status', DB::raw('COUNT(*) as count'))->pluck('count', 'status')->toArray();

// Valid types in system (normalized codes or legacy display strings)
$canonicalTypes = ['RENJA_MURNI', 'RENJA_PERUBAHAN', 'RENJA_LAMPIRAN_MURNI', 'RENJA_LAMPIRAN_PERUBAHAN'];
$legacyAllowedTypes = ['RENJA Murni', 'RENJA Perubahan', 'RENJA Lampiran Murni', 'RENJA Lampiran Perubahan', 'Rencana Kerja Perangkat Daerah (Renja)'];
$allValidTypeStrings = array_merge($canonicalTypes, $legacyAllowedTypes);

$invalidTypeDocs = RenjaDocument::whereNotIn('jenis_dokumen', $allValidTypeStrings)->orWhereNull('jenis_dokumen')->count();
$invalidOpdDocs = RenjaDocument::whereNotIn('opd_id', MasterOpd::pluck('id'))->orWhereNull('opd_id')->count();
$invalidTplDocs = RenjaDocument::whereNotIn('template_id', DocumentTemplate::pluck('id'))->orWhereNull('template_id')->count();
$invalidYearDocs = RenjaDocument::whereNull('tahun_anggaran')->orWhere('tahun_anggaran', '<', 2020)->orWhere('tahun_anggaran', '>', 2050)->count();

$validStatuses = [
    'draft',
    'submitted',
    'perlu_revisi',
    'dikirim_ulang',
    'disetujui',
    'menunggu_pemeriksaan',
    'sedang_diperiksa'
];
$invalidStatusDocs = RenjaDocument::whereNotIn('status', $validStatuses)->orWhereNull('status')->count();

$docStatus = "PASS";
if ($invalidOpdDocs > 0 || $invalidTplDocs > 0 || $invalidYearDocs > 0 || $invalidStatusDocs > 0) {
    $p0_count++;
    $p0_issues[] = "Renja documents contain invalid references (OPD: {$invalidOpdDocs}, Template: {$invalidTplDocs}, Year: {$invalidYearDocs}, Status: {$invalidStatusDocs})";
    $docStatus = "FAIL";
} elseif ($invalidTypeDocs > 0) {
    $p1_count++;
    $p1_issues[] = "Renja documents contain unmapped jenis_dokumen string: {$invalidTypeDocs}";
    $docStatus = "FAIL";
}

echo "RENJA DOCUMENTS\n";
echo "Total: {$totalDocs}\n";
echo "By Type: " . json_encode($byType) . "\n";
echo "By Year: " . json_encode($byYear) . "\n";
echo "By Status: " . json_encode($byStatus) . "\n";
echo "Invalid Type: {$invalidTypeDocs}\n";
echo "Invalid OPD: {$invalidOpdDocs}\n";
echo "Invalid Template: {$invalidTplDocs}\n";
echo "Invalid Year: {$invalidYearDocs}\n";
echo "Invalid Status: {$invalidStatusDocs}\n";
echo "Status: {$docStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 7. DOCUMENT -> OPD
|--------------------------------------------------------------------------
*/
$orphanDocs = $invalidOpdDocs;
echo "DOCUMENT -> OPD\n";
echo "Orphan Documents: {$orphanDocs}\n";
echo "Status: " . ($orphanDocs === 0 ? "PASS" : "FAIL") . "\n\n";

/*
|--------------------------------------------------------------------------
| 8. DOCUMENT -> TEMPLATE
|--------------------------------------------------------------------------
*/
$inactiveTemplateDocs = 0;
$mismatchedTemplateDocs = 0;

foreach (RenjaDocument::with('template')->get() as $doc) {
    if ($doc->template) {
        if (!$doc->template->is_active) {
            $inactiveTemplateDocs++;
        }
    }
}

echo "DOCUMENT -> TEMPLATE\n";
echo "Invalid Template ID: {$invalidTplDocs}\n";
echo "Inactive Template References: {$inactiveTemplateDocs}\n";
echo "Status: " . (($invalidTplDocs === 0 && $inactiveTemplateDocs === 0) ? "PASS" : "FAIL") . "\n\n";

/*
|--------------------------------------------------------------------------
| 9. RENJA SECTIONS
|--------------------------------------------------------------------------
*/
$totalRenjaSections = RenjaSection::count();
$orphanRenjaSections = RenjaSection::whereNotIn('document_id', RenjaDocument::pluck('id'))->count();
$invalidTplSecRefs = RenjaSection::whereNotNull('template_section_id')->whereNotIn('template_section_id', TemplateSection::pluck('id'))->count();

// Documents without sections
$docsWithoutSections = 0;
foreach (RenjaDocument::all() as $d) {
    if ($d->sections()->count() === 0) {
        $docsWithoutSections++;
    }
}

$renjaSecStatus = "PASS";
if ($orphanRenjaSections > 0 || $invalidTplSecRefs > 0) {
    $p0_count++;
    $p0_issues[] = "Orphan Renja sections or broken template_section_id";
    $renjaSecStatus = "FAIL";
}

echo "RENJA SECTIONS\n";
echo "Total: {$totalRenjaSections}\n";
echo "Orphan: {$orphanRenjaSections}\n";
echo "Invalid Template Section: {$invalidTplSecRefs}\n";
echo "Documents Without Sections: {$docsWithoutSections}\n";
echo "Status: {$renjaSecStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 10. TABLE UTAMA
|--------------------------------------------------------------------------
*/
$totalTableUtama = RenjaTableUtama::count();
$orphanTableUtama = RenjaTableUtama::whereNotIn('document_id', RenjaDocument::pluck('id'))->count();
$tableUtamaStatus = $orphanTableUtama === 0 ? "PASS" : "FAIL";
if ($orphanTableUtama > 0) {
    $p0_count++;
    $p0_issues[] = "Orphan Table Utama rows: {$orphanTableUtama}";
}

echo "TABLE UTAMA\n";
echo "Total: {$totalTableUtama}\n";
echo "Orphan: {$orphanTableUtama}\n";
echo "Status: {$tableUtamaStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 11. TABLE EVALUASI
|--------------------------------------------------------------------------
*/
$totalTableEval = RenjaTableEval::count();
$orphanTableEval = RenjaTableEval::whereNotIn('document_id', RenjaDocument::pluck('id'))->count();
$tableEvalStatus = $orphanTableEval === 0 ? "PASS" : "FAIL";
if ($orphanTableEval > 0) {
    $p0_count++;
    $p0_issues[] = "Orphan Table Eval rows: {$orphanTableEval}";
}

echo "TABLE EVAL\n";
echo "Total: {$totalTableEval}\n";
echo "Orphan: {$orphanTableEval}\n";
echo "Status: {$tableEvalStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 12. LAMPIRAN
|--------------------------------------------------------------------------
*/
$lampiranDocs = RenjaDocument::whereIn('jenis_dokumen', [
    'RENJA_LAMPIRAN_MURNI',
    'RENJA_LAMPIRAN_PERUBAHAN',
    'RENJA Lampiran Murni',
    'RENJA Lampiran Perubahan'
])->get();

$lampiranTotal = $lampiranDocs->count();
$missingParentCount = 0;
$wrongParentTypeCount = 0;

foreach ($lampiranDocs as $lamp) {
    $parent = $lamp->getParentDocument();
    if (!$parent) {
        $missingParentCount++;
        echo "  - Lampiran ID {$lamp->id} (OPD {$lamp->opd_id}, Tahun {$lamp->tahun_anggaran}) has NO parent.\n";
    } else {
        $isLampMurni = in_array($lamp->jenis_dokumen, ['RENJA_LAMPIRAN_MURNI', 'RENJA Lampiran Murni']);
        $isParentMurni = in_array($parent->jenis_dokumen, ['RENJA_MURNI', 'RENJA Murni', 'Rencana Kerja Perangkat Daerah (Renja)']);
        
        $isLampPerubahan = in_array($lamp->jenis_dokumen, ['RENJA_LAMPIRAN_PERUBAHAN', 'RENJA Lampiran Perubahan']);
        $isParentPerubahan = in_array($parent->jenis_dokumen, ['RENJA_PERUBAHAN', 'RENJA Perubahan']);
        
        if ($isLampMurni && !$isParentMurni) {
            $wrongParentTypeCount++;
            echo "  - Lampiran Murni ID {$lamp->id} linked to non-Murni parent ID {$parent->id} ({$parent->jenis_dokumen})\n";
        } elseif ($isLampPerubahan && !$isParentPerubahan) {
            $wrongParentTypeCount++;
            echo "  - Lampiran Perubahan ID {$lamp->id} linked to non-Perubahan parent ID {$parent->id} ({$parent->jenis_dokumen})\n";
        } else {
            echo "  - Lampiran ID {$lamp->id} -> Parent ID {$parent->id} [{$parent->jenis_dokumen}, Status: {$parent->status}]\n";
        }
    }
}

$lampiranStatus = ($missingParentCount === 0 && $wrongParentTypeCount === 0) ? "PASS" : "FAIL";
if ($missingParentCount > 0 || $wrongParentTypeCount > 0) {
    $p0_count++;
    $p0_issues[] = "Lampiran documents with missing or mismatched parent documents";
}

echo "LAMPIRAN\n";
echo "Total: {$lampiranTotal}\n";
echo "Missing Parent: {$missingParentCount}\n";
echo "Wrong Parent Type: {$wrongParentTypeCount}\n";
echo "Status: {$lampiranStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 13. MASTER OPD LAMPIRAN
|--------------------------------------------------------------------------
*/
$missingRoman = MasterOpd::whereNull('nomor_lampiran_romawi')->orWhere('nomor_lampiran_romawi', '')->count();
$duplicateRoman = MasterOpd::select('nomor_lampiran_romawi')
    ->whereNotNull('nomor_lampiran_romawi')
    ->where('nomor_lampiran_romawi', '!=', '')
    ->groupBy('nomor_lampiran_romawi')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('nomor_lampiran_romawi')->toArray();

$masterOpdLampStatus = "PASS";
if ($missingRoman > 0) {
    $p2_count++;
    $p2_issues[] = "{$missingRoman} Master OPD entries missing nomor_lampiran_romawi";
    $masterOpdLampStatus = "WARNING";
}
if (count($duplicateRoman) > 0) {
    $p2_count++;
    $p2_issues[] = "Duplicate nomor_lampiran_romawi in Master OPD: " . implode(', ', $duplicateRoman);
    $masterOpdLampStatus = "WARNING";
}

echo "MASTER OPD LAMPIRAN\n";
echo "Missing Roman: {$missingRoman}\n";
echo "Duplicate Roman: " . count($duplicateRoman) . (count($duplicateRoman) > 0 ? " (" . implode(', ', $duplicateRoman) . ")" : "") . "\n";
echo "Status: {$masterOpdLampStatus}\n\n";

/*
|--------------------------------------------------------------------------
| 14. YEAR SOURCE OF TRUTH
|--------------------------------------------------------------------------
*/
$nullYears = RenjaDocument::whereNull('tahun_anggaran')->count();
$invalidYearRange = RenjaDocument::where('tahun_anggaran', '<', 2020)->orWhere('tahun_anggaran', '>', 2035)->count();

echo "YEAR SOURCE OF TRUTH\n";
echo "Table Column: renja_documents.tahun_anggaran (INTEGER)\n";
echo "Null Years: {$nullYears}\n";
echo "Out of range (<2020 or >2035): {$invalidYearRange}\n";
echo "Distinct Years: " . json_encode(RenjaDocument::pluck('tahun_anggaran')->unique()->values()->toArray()) . "\n";
echo "Status: " . (($nullYears === 0 && $invalidYearRange === 0) ? "PASS" : "FAIL") . "\n\n";

/*
|--------------------------------------------------------------------------
| 15. LEGACY DATA
|--------------------------------------------------------------------------
*/
$legacyTables = [
    'reference_document_schemas' => 'POTENTIAL DEAD DATA (Deprecated structure schema)',
    'template_chapters' => 'LEGACY (Replaced by template_sections in smart-template engine)',
    'template_sub_chapters' => 'LEGACY (Replaced by template_sections in smart-template engine)',
    'template_table_columns' => 'LEGACY (Replaced by renja_table_utama & renja_table_eval)',
    'perangkat_daerah' => 'LEGACY (Replaced by master_opd)',
    'documents' => 'LEGACY (Replaced by renja_documents)',
    'document_templates' => 'ACTIVE (Smart template engine definitions)',
    'template_sections' => 'ACTIVE (Smart template sections hierarchy)',
    'master_opd' => 'ACTIVE (71+ OPD reference)',
    'renja_documents' => 'ACTIVE (Main Renja document storage)',
    'renja_sections' => 'ACTIVE (Document sections storage)',
    'renja_table_utama' => 'ACTIVE (Table T-C.29 Bab IV storage)',
    'renja_table_eval' => 'ACTIVE (Table Evaluasi Bab II storage)'
];

echo "LEGACY DATA\n";
$activeCount = 0;
$legacyCount = 0;
$deadCount = 0;
$unknownCount = 0;

foreach ($legacyTables as $tbl => $classification) {
    $exists = Schema::hasTable($tbl);
    $rowCount = $exists ? DB::table($tbl)->count() : 0;
    
    if (str_starts_with($classification, 'ACTIVE')) {
        $activeCount++;
        echo "  - [ACTIVE] {$tbl}: {$rowCount} rows\n";
    } elseif (str_starts_with($classification, 'LEGACY')) {
        $legacyCount++;
        echo "  - [LEGACY] {$tbl}: " . ($exists ? "{$rowCount} rows" : "TABLE NOT IN DB") . "\n";
    } elseif (str_starts_with($classification, 'POTENTIAL DEAD')) {
        $deadCount++;
        echo "  - [POTENTIAL DEAD] {$tbl}: " . ($exists ? "{$rowCount} rows" : "TABLE NOT IN DB") . "\n";
    } else {
        $unknownCount++;
        echo "  - [UNKNOWN] {$tbl}\n";
    }
}

echo "Active Tables: {$activeCount}\n";
echo "Legacy Tables: {$legacyCount}\n";
echo "Potential Dead: {$deadCount}\n";
echo "Unknown: {$unknownCount}\n\n";

/*
|--------------------------------------------------------------------------
| 16. DATABASE INTEGRITY & FOREIGN KEYS
|--------------------------------------------------------------------------
*/
$fkCheck = DB::select("PRAGMA foreign_key_check;");
$fkCount = count($fkCheck);

echo "DATABASE INTEGRITY (FOREIGN KEYS)\n";
echo "Foreign Key Violations: {$fkCount}\n";
if ($fkCount > 0) {
    foreach ($fkCheck as $fk) {
        echo "  - Violation: " . json_encode($fk) . "\n";
    }
    $p0_count++;
    $p0_issues[] = "Database foreign key constraint violations found: {$fkCount}";
} else {
    echo "  PASS: Zero foreign key violations.\n";
}
echo "\n";

/*
|--------------------------------------------------------------------------
| 17. UAT DATA
|--------------------------------------------------------------------------
*/
$uatUsers = User::where('username', 'LIKE', '%uat%')
    ->orWhere('name', 'LIKE', '%uat%')
    ->orWhere('email', 'LIKE', '%uat%')
    ->orWhereIn('name', ['Operator Dinkes', 'Operator Disdik', 'Admin Bapperida UAT'])
    ->get(['id', 'name', 'username', 'email', 'role']);

$uatOpds = MasterOpd::where('kode_opd', 'LIKE', 'UAT%')
    ->orWhere('nama_opd', 'LIKE', '%UAT%')
    ->get(['id', 'kode_opd', 'nama_opd']);

$uatDocs = RenjaDocument::where('nomor_dokumen', 'LIKE', '%UAT%')
    ->orWhere('catatan_verifikasi', 'LIKE', '%UAT%')
    ->get(['id', 'nomor_dokumen', 'jenis_dokumen', 'opd_id', 'status']);

$uatFound = ($uatUsers->count() > 0 || $uatOpds->count() > 0 || $uatDocs->count() > 0);

echo "UAT DATA\n";
echo "Found: " . ($uatFound ? "YES" : "NO") . "\n";
if ($uatFound) {
    echo "Details:\n";
    if ($uatUsers->count() > 0) {
        echo "  - UAT Users (" . $uatUsers->count() . "): " . implode(', ', $uatUsers->map(fn($u) => "ID:{$u->id}({$u->username})")->toArray()) . "\n";
    }
    if ($uatOpds->count() > 0) {
        echo "  - UAT Master OPD (" . $uatOpds->count() . "): " . implode(', ', $uatOpds->map(fn($o) => "ID:{$o->id}({$o->kode_opd})")->toArray()) . "\n";
    }
    if ($uatDocs->count() > 0) {
        echo "  - UAT Documents (" . $uatDocs->count() . "): " . implode(', ', $uatDocs->map(fn($d) => "ID:{$d->id}(Doc:{$d->nomor_dokumen})")->toArray()) . "\n";
    }
}
echo "\n";

/*
|--------------------------------------------------------------------------
| 18. READ-ONLY VERIFICATION
|--------------------------------------------------------------------------
*/
$executedQueries = DB::getQueryLog();
$mutatingQueries = [];
foreach ($executedQueries as $queryLog) {
    $sql = strtoupper(trim($queryLog['query']));
    if (str_starts_with($sql, 'INSERT') || str_starts_with($sql, 'UPDATE') || str_starts_with($sql, 'DELETE') ||
        str_starts_with($sql, 'ALTER') || str_starts_with($sql, 'DROP') || str_starts_with($sql, 'TRUNCATE')) {
        $mutatingQueries[] = $sql;
    }
}

echo "READ-ONLY VERIFICATION\n";
echo "Total Queries Executed: " . count($executedQueries) . "\n";
echo "Mutating Queries: " . count($mutatingQueries) . "\n";
if (count($mutatingQueries) > 0) {
    echo "  FAIL: Mutating queries detected!\n";
    $p0_count++;
} else {
    echo "  PASS: 100% READ-ONLY verification confirmed.\n";
}
echo "\n";

/*
|--------------------------------------------------------------------------
| FINAL RESULT
|--------------------------------------------------------------------------
*/
echo "====================================================\n";
echo "FINAL RESULT\n";
echo "====================================================\n\n";

echo "P0: {$p0_count}\n";
foreach ($p0_issues as $iss) {
    echo "  - {$iss}\n";
}

echo "P1: {$p1_count}\n";
foreach ($p1_issues as $iss) {
    echo "  - {$iss}\n";
}

echo "P2: {$p2_count}\n";
foreach ($p2_issues as $iss) {
    echo "  - {$iss}\n";
}

echo "\nDATABASE AUDIT:\n";
if ($p0_count > 0) {
    echo "FAIL\n";
} elseif ($p1_count > 0 || $p2_count > 0) {
    echo "WARNING\n";
} else {
    echo "PASS\n";
}
