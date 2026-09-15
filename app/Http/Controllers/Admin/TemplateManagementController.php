<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentTemplate;
use App\Models\TemplateSection;
use App\Models\RenjaDocument;
use App\Services\DocumentTemplateService;

class TemplateManagementController extends Controller
{
    protected DocumentTemplateService $templateService;

    public function __construct(DocumentTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Tampilkan Halaman Master Template Dokumen (Index).
     */
    public function index()
    {
        $activeYear = (int) session('active_ta', (int) date('Y'));
        $templates = $this->templateService->getAllTemplates();

        // Hitung usage count per template dari database
        foreach ($templates as $tpl) {
            $tpl->usage_count = RenjaDocument::where('template_id', $tpl->id)->count();

            // Mapping Target Active Cycle Year
            $tpl->target_cycle_year = str_contains($tpl->code, 'PERUBAHAN')
                ? $activeYear
                : $activeYear + 1;
        }

        return view('admin.templates.index', compact('templates', 'activeYear'));
    }

    /**
     * Tampilkan Detail Struktur Master Template Dokumen (Show).
     */
    public function show($id)
    {
        $activeYear = (int) session('active_ta', (int) date('Y'));
        $template = DocumentTemplate::with(['sections' => function ($q) {
            $q->orderBy('sequence', 'asc');
        }])->findOrFail($id);

        $usageCount = RenjaDocument::where('template_id', $template->id)->count();
        $targetCycleYear = str_contains($template->code, 'PERUBAHAN') ? $activeYear : $activeYear + 1;

        $stats = [
            'total_sections' => $template->sections->count(),
            'required_count' => $template->sections->where('is_required', true)->count(),
            'editable_count' => $template->sections->where('is_editable', true)->count(),
            'automatic_count' => $template->sections->where('is_automatic', true)->count(),
            'usage_count' => $usageCount,
            'target_cycle_year' => $targetCycleYear,
        ];

        // Group sections by chapter for tree rendering
        $groupedSections = $template->sections->groupBy(function ($sec) {
            if ($sec->section_type === 'chapter') {
                return $sec->code;
            }
            if ($sec->parent) {
                return $sec->parent->code;
            }
            return 'BAGIAN LAINNYA';
        });

        return view('admin.templates.show', compact('template', 'stats', 'groupedSections', 'activeYear'));
    }

    /**
     * Toggle status is_active template (BR-030).
     */
    public function toggleStatus($id)
    {
        $template = $this->templateService->toggleTemplateStatus($id);

        $statusText = $template->is_active ? 'DISEDIAKAN (AKTIF)' : 'DINONAKTIFKAN';
        return back()->with('success', "Status template {$template->name} berhasil diubah menjadi {$statusText}.");
    }

    /**
     * Form Edit Konfigurasi & Struktur Template.
     */
    public function edit($id)
    {
        $template = DocumentTemplate::with('sections')->findOrFail($id);
        $usageCount = RenjaDocument::where('template_id', $template->id)->count();

        return view('admin.templates.edit', compact('template', 'usageCount'));
    }

    /**
     * Update Konfigurasi Format Kertas F4 & Options.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'paper_width_mm' => 'required|integer',
            'paper_height_mm' => 'required|integer',
            'margin_top_cm' => 'required|numeric',
            'margin_bottom_cm' => 'required|numeric',
            'margin_left_cm' => 'required|numeric',
            'margin_right_cm' => 'required|numeric',
        ]);

        $this->templateService->updateTemplateConfig($id, $request->all());

        return back()->with('success', 'Konfigurasi format F4 & metadata template berhasil diperbarui.');
    }

    /**
     * Hapus Master Template jika belum digunakan dokumen (BR-24 Protection).
     */
    public function destroy($id)
    {
        try {
            $this->templateService->deleteTemplate($id);
            return redirect()->route('admin.templates.index')->with('success', 'Master template berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tambah Seksi Bab/Subbab Baru ke Master Template.
     */
    public function storeSection(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'section_type' => 'required|in:chapter,subchapter,cover,preface,appendix',
        ]);

        try {
            $this->templateService->addTemplateSection($id, $request->all());
            return back()->with('success', 'Seksi bab/subbab baru berhasil ditambahkan ke master template.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update Seksi Bab/Subbab Master Template.
     */
    public function updateSection(Request $request, $id, $sectionId)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
        ]);

        try {
            $this->templateService->updateTemplateSection($sectionId, $request->all());
            return back()->with('success', 'Seksi bab/subbab master template berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Hapus Seksi Bab/Subbab dari Master Template.
     */
    public function destroySection($id, $sectionId)
    {
        try {
            $this->templateService->deleteTemplateSection($sectionId);
            return back()->with('success', 'Seksi bab berhasil dihapus dari master template.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
