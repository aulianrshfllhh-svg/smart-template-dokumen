<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RenjaFixDocumentController extends Controller
{
    /**
     * Redirect legacy Dokumen Fix index route to unified Arsip RENJA.
     */
    public function index(Request $request)
    {
        return redirect()->route('renja.archive.index', $request->query());
    }

    /**
     * Redirect legacy Dokumen Fix document route to unified Arsip RENJA.
     */
    public function showDocument(Request $request, int $id)
    {
        $params = array_merge(['id' => $id], $request->query());
        return redirect()->route('renja.archive.document', $params);
    }

    /**
     * Redirect legacy Dokumen Fix BAB route to unified Arsip RENJA.
     */
    public function showBab(Request $request, int $id, string $babCode)
    {
        $params = array_merge(['id' => $id, 'bab' => urldecode($babCode)], $request->query());
        return redirect()->route('renja.archive.document', $params);
    }

    /**
     * Redirect legacy Dokumen Fix Section route to unified Arsip RENJA.
     */
    public function showSection(Request $request, int $id, int $sectionId)
    {
        $params = array_merge(['id' => $id, 'section' => $sectionId], $request->query());
        return redirect()->route('renja.archive.document', $params);
    }
}
