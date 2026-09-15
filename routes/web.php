<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RenjaArchiveController;
use App\Http\Controllers\RenjaDocumentController;
use App\Http\Controllers\RenjaFixDocumentController;
use App\Http\Controllers\RenjaMurniController;
use App\Http\Controllers\RenjaEditorController;
use App\Http\Controllers\DocumentAutoFixController;
use App\Http\Controllers\DocumentFormatterController;
use App\Http\Controllers\ReferenceDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BapperidaMonitoringController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\VerificationWorkspaceController;
use App\Http\Controllers\Admin\TemplateManagementController;
use App\Http\Controllers\Admin\OpdMonitoringController;
use App\Http\Controllers\Admin\MasterOpdController;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\StripBoldTags;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/sandbox-editor', function () {
    return view('sandbox-editor');
});

Route::get('/null', function () {
    return redirect()->route('renja.index');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Portal Hub Dashboard Utama
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Switch Active Tahun Anggaran (TA) Session
    Route::match(['get', 'post'], '/set-active-ta', function (\Illuminate\Http\Request $request) {
        $ta = (int) $request->input('tahun_anggaran', 2027);
        if ($ta < 2020 || $ta > 2040) {
            $ta = 2027;
        }
        session(['active_ta' => $ta]);

        $referer = $request->headers->get('referer');
        if ($referer) {
            $parsed = parse_url($referer);
            $queryParams = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
            }

            // Jika referer memiliki query parameter tahun_anggaran, ganti nilainya
            if (array_key_exists('tahun_anggaran', $queryParams)) {
                $queryParams['tahun_anggaran'] = $ta;
                $newQuery = http_build_query($queryParams);
                $newUrl = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') .
                    (isset($parsed['port']) ? ':' . $parsed['port'] : '') .
                    ($parsed['path'] ?? '/') .
                    (!empty($newQuery) ? '?' . $newQuery : '');
                return redirect($newUrl)->with('success', "Tahun Anggaran (TA) aktif berhasil diubah ke {$ta}.");
            }

            // Jika berada di halaman renja-workspace tanpa query parameter
            if (str_contains($parsed['path'] ?? '', 'renja-workspace')) {
                return redirect()->route('renja.workspace', ['tahun_anggaran' => $ta])
                    ->with('success', "Tahun Anggaran (TA) aktif berhasil diubah ke {$ta}.");
            }
        }

        return back()->with('success', "Tahun Anggaran (TA) aktif berhasil diubah ke {$ta}.");
    })->name('set-ta');

    // Modul C: Monitoring & Verifikasi Pengawasan Bapperida
    Route::get('/bapperida/monitoring', [BapperidaMonitoringController::class, 'index'])
        ->name('bapperida.monitoring');

    Route::get('/renja-documents', [RenjaDocumentController::class, 'index'])
        ->name('renja.index');

    Route::get('/renja-workspace', [RenjaDocumentController::class, 'workspace'])
        ->name('renja.workspace');

    // Dedicated Modul RENJA Lampiran (Murni & Perubahan)
    Route::get('/renja-lampiran', [\App\Http\Controllers\RenjaLampiranController::class, 'index'])
        ->name('renja.lampiran.index');
    Route::post('/renja-lampiran/store-murni', [\App\Http\Controllers\RenjaLampiranController::class, 'storeMurni'])
        ->name('renja.lampiran.storeMurni');
    Route::post('/renja-lampiran/store-perubahan', [\App\Http\Controllers\RenjaLampiranController::class, 'storePerubahan'])
        ->name('renja.lampiran.storePerubahan');
    Route::post('/renja-lampiran/upload-murni', [\App\Http\Controllers\RenjaLampiranController::class, 'uploadMurni'])
        ->name('renja.lampiran.uploadMurni');
    Route::post('/renja-lampiran/upload-perubahan', [\App\Http\Controllers\RenjaLampiranController::class, 'uploadPerubahan'])
        ->name('renja.lampiran.uploadPerubahan');

    // Modul Dokumen Fix (Disetujui) - File Explorer Repository Dokumen Final
    Route::get('/renja/dokumen-fix', [RenjaFixDocumentController::class, 'index'])
        ->name('renja.fix.index');
    Route::get('/renja/dokumen-fix/{id}', [RenjaFixDocumentController::class, 'showDocument'])
        ->name('renja.fix.show');
    Route::get('/renja/dokumen-fix/{id}/bab/{babCode}', [RenjaFixDocumentController::class, 'showBab'])
        ->name('renja.fix.bab');
    Route::get('/renja/dokumen-fix/{id}/section/{sectionId}', [RenjaFixDocumentController::class, 'showSection'])
        ->name('renja.fix.section');

    // Modul Arsip RENJA - File Explorer Repository Dokumen Historis
    Route::get('/renja/arsip', [RenjaArchiveController::class, 'index'])
        ->name('renja.archive.index');
    Route::get('/renja/arsip/ta/{year}', [RenjaArchiveController::class, 'showYear'])
        ->name('renja.archive.year');
    Route::get('/renja/arsip/doc/{id}', [RenjaArchiveController::class, 'showDocument'])
        ->name('renja.archive.document');
    Route::get('/renja/arsip/doc/{id}/bab/{babCode}', [RenjaArchiveController::class, 'showBab'])
        ->name('renja.archive.bab');
    Route::get('/renja/arsip/doc/{id}/section/{sectionId}', [RenjaArchiveController::class, 'showSection'])
        ->name('renja.archive.section');
    Route::post('/renja/arsip/doc/{id}/archive', [RenjaArchiveController::class, 'archive'])
        ->name('renja.archive.archive');
    Route::post('/renja/arsip/doc/{id}/restore', [RenjaArchiveController::class, 'restore'])
        ->name('renja.archive.restore');

    Route::post('/renja-documents/create-murni', [RenjaDocumentController::class, 'storeMurni'])
        ->name('renja.storeMurni');

    Route::post('/renja-documents/create-perubahan', [RenjaDocumentController::class, 'storePerubahan'])
        ->name('renja.storePerubahan');

    Route::post('/renja-documents/generate-lampiran', [RenjaDocumentController::class, 'generateLampiran'])
        ->name('renja.generateLampiran');


    Route::get('/renja-documents/create', [RenjaDocumentController::class, 'create'])
        ->name('renja.create');

    Route::post('/renja-documents', [RenjaDocumentController::class, 'store'])
        ->middleware(StripBoldTags::class)
        ->name('renja.store');

    Route::post('/renja-documents/{id}/submit', [RenjaDocumentController::class, 'submit'])
        ->name('renja.submit');

    Route::post('/renja-documents/{id}/finalize', [RenjaDocumentController::class, 'finalize'])
        ->name('renja.finalize');

    Route::post('/renja-documents/{id}/update-name', [RenjaDocumentController::class, 'updateDocumentName'])
        ->name('renja.updateName');

    Route::get('/renja-documents/{id}', [RenjaDocumentController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('renja.show');

    Route::get('/renja-documents/{id}/preview', [RenjaDocumentController::class, 'preview'])
        ->where('id', '[0-9]+')
        ->name('renja.preview');

    Route::get('/renja-documents/{id}/preview-pdf', [RenjaDocumentController::class, 'previewPdf'])
        ->where('id', '[0-9]+')
        ->name('renja.preview-pdf');

    Route::get('/renja-documents/{id}/print', [RenjaDocumentController::class, 'print'])
        ->where('id', '[0-9]+')
        ->name('renja.print');

    Route::get('/renja-documents/{id}/original-file', [RenjaDocumentController::class, 'originalFile'])
        ->where('id', '[0-9]+')
        ->name('renja.original-file');

    Route::get('/renja-documents/{id}/export-word', [RenjaDocumentController::class, 'exportWord'])
        ->where('id', '[0-9]+')
        ->name('renja.exportWord');

    Route::get('/renja-documents/{id}/export-pdf', [RenjaDocumentController::class, 'exportPdf'])
        ->where('id', '[0-9]+')
        ->name('renja.exportPdf');

    Route::get('/editor', function () {
        return view('editor');
    })->name('standalone.editor');

    Route::get('/renja-documents/{id}/editor', [RenjaEditorController::class, 'index'])
        ->name('renja.editor');

    Route::post('/renja-documents/{id}/sections/{sectionId}', [RenjaEditorController::class, 'updateSection'])
        ->middleware(StripBoldTags::class)
        ->name('renja.editor.updateSection');

    // Alias route expected by tests
    Route::post('/renja-documents/{id}/sections/{sectionId}/update', [RenjaEditorController::class, 'updateSection'])
        ->middleware(StripBoldTags::class)
        ->name('renja.editor.section.update');

    Route::post('/renja-documents/{id}/update-bab-title', [RenjaEditorController::class, 'updateBabTitle'])
        ->name('renja.editor.updateBabTitle');

    Route::post('/renja-documents/{id}/sections/{sectionId}/update-title', [RenjaEditorController::class, 'updateSubBabTitle'])
        ->name('renja.editor.updateSubBabTitle');

    Route::delete('/renja-documents/{id}/babs/{babCode}', [RenjaEditorController::class, 'deleteBab'])
        ->name('renja.editor.deleteBab');

    Route::delete('/renja-documents/{id}/sections/{sectionId}', [RenjaEditorController::class, 'deleteSection'])
        ->name('renja.editor.deleteSection');

    Route::post('/renja-documents/{id}/add-bab', [RenjaEditorController::class, 'addBab'])
        ->name('renja.editor.addBab');

    Route::post('/renja-documents/{id}/add-sub-bab', [RenjaEditorController::class, 'addSubBab'])
        ->name('renja.editor.addSubBab');

    Route::post('/renja-documents/{id}/front-matter', [RenjaEditorController::class, 'addFrontMatter'])
        ->name('renja.editor.addFrontMatter');

    Route::post('/renja-documents/{id}/sections/{sectionId}/acuan', [RenjaEditorController::class, 'loadAcuanDraft'])
        ->name('renja.editor.acuan');

    Route::post('/renja-documents/{id}/sections/{sectionId}/autofix', [RenjaEditorController::class, 'autofixSection'])
        ->name('renja.section.autofix');

    Route::post('/renja-documents/{id}/switch-template', [RenjaEditorController::class, 'switchTemplate'])
        ->name('renja.editor.switchTemplate');

    Route::post('/renja-documents/templates/store', [RenjaEditorController::class, 'storeTemplate'])
        ->name('renja.templates.store');

    Route::post('/renja-documents/{id}/update-cover', [RenjaEditorController::class, 'updateCover'])
        ->name('renja.editor.updateCover');

    Route::post('/renja-documents/{id}/sipd', [RenjaEditorController::class, 'addTableEval'])
        ->name('renja.editor.addTableEval');

    Route::delete('/renja-documents/{id}', [RenjaDocumentController::class, 'destroy'])
        ->name('renja.destroy');

    // Download Blank Word Template RENJA Resmi (Murni & Perubahan)
    Route::get('/renja-templates/{templateCode}/download', [RenjaDocumentController::class, 'downloadTemplate'])
        ->name('renja.templates.download');

    // Modul B: Mesin Cuci Dokumen Renja (Auto-Formatter MS Word .docx via Python)
    Route::get('/formatter/upload', [DocumentFormatterController::class, 'index'])
        ->name('formatter.upload');

    Route::get('/formatter', [DocumentFormatterController::class, 'index'])
        ->name('formatter.index');

    Route::post('/formatter/process', [DocumentFormatterController::class, 'process'])
        ->name('formatter.process');

    Route::get('/formatter/download/{filename}', [DocumentFormatterController::class, 'downloadFile'])
        ->name('formatter.download');

    // Auto-Detect Modul dari Dokumen Acuan (Reference Documents)
    Route::get('/reference-documents', [ReferenceDocumentController::class, 'index'])
        ->name('reference-documents.index');
    Route::get('/reference-documents/create', [ReferenceDocumentController::class, 'create'])
        ->name('reference-documents.create');
    Route::post('/reference-documents', [ReferenceDocumentController::class, 'store'])
        ->name('reference-documents.store');
    Route::get('/reference-documents/{id}', [ReferenceDocumentController::class, 'show'])
        ->name('reference-documents.show');
    Route::post('/reference-documents/{id}/approve', [ReferenceDocumentController::class, 'approve'])
        ->name('reference-documents.approve');
    Route::post('/reference-documents/{id}/reject', [ReferenceDocumentController::class, 'reject'])
        ->name('reference-documents.reject');
    Route::post('/reference-documents/{id}/apply', [ReferenceDocumentController::class, 'applyToDocument'])
        ->name('reference-documents.apply');
    Route::post('/reference-documents/sub-chapter/{id}', [ReferenceDocumentController::class, 'updateSubChapter'])
        ->name('reference-documents.updateSubChapter');
    Route::delete('/reference-documents/{id}', [ReferenceDocumentController::class, 'destroy'])
        ->name('reference-documents.destroy');

    // Modul Dokumen RKPD (Rencana Kerja Pemerintah Daerah)
    Route::get('/rkpd-documents', [\App\Http\Controllers\RkpdDocumentController::class, 'index'])
        ->name('rkpd.index');
    Route::get('/rkpd-documents/workspace', [\App\Http\Controllers\RkpdDocumentController::class, 'workspace'])
        ->name('rkpd.workspace');
    Route::get('/rkpd-documents/archive', [\App\Http\Controllers\RkpdDocumentController::class, 'archive'])
        ->name('rkpd.archive');

    /*
    |--------------------------------------------------------------------------
    | Operator OPD Routes (role: operator)
    |--------------------------------------------------------------------------
    */
    Route::middleware([EnsureRole::class . ':operator,admin,verifikator,staff'])->prefix('operator')->name('operator.')->group(function () {
        Route::get('/dashboard', [RenjaDocumentController::class, 'index'])->name('dashboard');

        // Modul RENJA Murni (Dokumen Saya > RENJA Murni)
        Route::get('/renja-murni', [RenjaMurniController::class, 'index'])->name('renja-murni.index');
        Route::post('/renja-murni/template', [RenjaMurniController::class, 'storeFromTemplate'])->name('renja-murni.store-template');
        Route::post('/renja-murni/upload', [RenjaMurniController::class, 'storeUpload'])->name('renja-murni.store-upload');
        Route::post('/renja-murni/upload-submit', [RenjaMurniController::class, 'uploadAndSubmit'])->name('renja-murni.upload-submit');
        Route::get('/renja-murni/{id}/validate', [RenjaMurniController::class, 'showValidation'])->name('renja-murni.validate');

        // Modul Template Saya > Template RENJA Murni
        Route::get('/templates/renja-murni', [RenjaMurniController::class, 'templatePreview'])->name('templates.renja-murni');

        // Dedicated Preview RENJA Lampiran Murni
        Route::get('/documents/{id}/lampiran/preview', [RenjaDocumentController::class, 'preview'])
            ->name('renja-lampiran.preview');

        // Dokumen Renja CRUD & Smart Template Editor
        Route::get('/renja/create', [RenjaDocumentController::class, 'create'])->name('renja.create');

        // Smart Template Editor Interaktif
        Route::get('/renja/{id}/editor', [RenjaEditorController::class, 'index'])->name('renja.editor');
        Route::post('/renja/{id}/sections/{sectionId}', [RenjaEditorController::class, 'updateSection'])
            ->middleware(StripBoldTags::class)
            ->name('renja.editor.updateSection');
        Route::post('/renja/{id}/add-bab', [RenjaEditorController::class, 'addBab'])->name('renja.editor.addBab');
        Route::post('/renja/{id}/add-sub-bab', [RenjaEditorController::class, 'addSubBab'])->name('renja.editor.addSubBab');
        Route::delete('/renja/{id}/sections/{sectionId}', [RenjaEditorController::class, 'deleteSection'])->name('renja.editor.deleteSection');
        Route::post('/renja/{id}/sections/{sectionId}/acuan', [RenjaEditorController::class, 'loadAcuanDraft'])->name('renja.editor.acuan');
        Route::post('/renja/{id}/sipd', [RenjaEditorController::class, 'addTableEval'])->name('renja.editor.addTableEval');

        // Apply "StripBoldTags" middleware to strip bold elements on store and update
        Route::post('/renja', [RenjaDocumentController::class, 'store'])
            ->middleware(StripBoldTags::class)
            ->name('renja.store');

        Route::get('/renja/{id}', [RenjaDocumentController::class, 'show'])->name('renja.show');
        Route::get('/renja/{id}/edit', [RenjaDocumentController::class, 'edit'])->name('renja.edit');

        Route::put('/renja/{id}', [RenjaDocumentController::class, 'update'])
            ->middleware(StripBoldTags::class)
            ->name('renja.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin & Verifikator Bapperida Workspace Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware([EnsureRole::class . ':admin,verifikator,staff'])->prefix('admin/verifikasi')->name('admin.verifikasi.')->group(function () {
        Route::get('/', [VerificationWorkspaceController::class, 'index'])->name('index');
        Route::get('/{id}/review', [VerificationWorkspaceController::class, 'review'])->name('review');
        Route::post('/{id}/decision', [VerificationWorkspaceController::class, 'processDecision'])->name('decision');
        Route::post('/{id}/assign', [VerificationWorkspaceController::class, 'assignVerificator'])->name('assign');
    });

    // Modul Manajemen Template (Template Management Engine)
    Route::middleware([EnsureRole::class . ':admin'])->prefix('admin/templates')->name('admin.templates.')->group(function () {
        Route::get('/', [TemplateManagementController::class, 'index'])->name('index');
        Route::post('/{id}/toggle-status', [TemplateManagementController::class, 'toggleStatus'])->name('toggleStatus');
        Route::get('/{id}', [TemplateManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TemplateManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TemplateManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [TemplateManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/sections', [TemplateManagementController::class, 'storeSection'])->name('storeSection');
        Route::put('/{id}/sections/{sectionId}', [TemplateManagementController::class, 'updateSection'])->name('updateSection');
        Route::delete('/{id}/sections/{sectionId}', [TemplateManagementController::class, 'destroySection'])->name('destroySection');
    });

    Route::middleware([EnsureRole::class . ':admin,verifikator,staff'])->group(function () {
        Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    });
    Route::get('/admin/review/{id}', [AdminController::class, 'reviewDocument'])->name('admin.review');
    Route::post('/admin/review/{id}/decision', [AdminController::class, 'processDecision'])->name('admin.decision');

    // Verifikator Dedicated Routes
    Route::get('/verifikator/dashboard', [DashboardController::class, 'verifikatorDashboard'])->name('verifikator.dashboard');
    Route::middleware([EnsureRole::class . ':verifikator'])->prefix('verifikator')->name('verifikator.')->group(function () {
        Route::get('/renja/{id}', [RenjaDocumentController::class, 'show'])->name('renja.show');
        Route::post('/renja/{id}/status', [RenjaDocumentController::class, 'updateStatus'])->name('renja.updateStatus');
    });

    // Staff Bapperida Dedicated Routes
    Route::get('/staff/dashboard', [DashboardController::class, 'staffDashboard'])->name('staff.dashboard');

    // Pimpinan Executive Dedicated Routes
    Route::get('/pimpinan/dashboard', [DashboardController::class, 'pimpinanDashboard'])->name('pimpinan.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Admin Bapperida Superadmin Routes (role: admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware([EnsureRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/review/{id}', [AdminController::class, 'reviewDocument'])->name('review');
        Route::post('/review/{id}/decision', [AdminController::class, 'processDecision'])->name('decision');
        Route::get('/renja/{id}', [RenjaDocumentController::class, 'show'])->name('renja.show');
        Route::post('/renja/{id}/status', [RenjaDocumentController::class, 'updateStatus'])->name('renja.updateStatus');

        // Master OPD Management Routes (List & Detail Perangkat Daerah)
        Route::get('/master-opd', [MasterOpdController::class, 'index'])->name('master_opd.index');
        Route::get('/master-opd/{id}', [MasterOpdController::class, 'show'])->name('master_opd.show');

        Route::get('/master-nomenklatur', function () {
            return view('admin.master_nomenklatur');
        })->name('master_nomenklatur.index');

        // Modul Monitoring OPD (Pantau Seluruh OPD & Progres Dokumen)
        Route::get('/monitoring-opd', [OpdMonitoringController::class, 'index'])->name('monitoring-opd.index');
        Route::get('/monitoring-opd/{opd}', [OpdMonitoringController::class, 'show'])->name('monitoring-opd.show');
    });

});
