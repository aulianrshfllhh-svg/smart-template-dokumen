<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RkpdDocumentController extends Controller
{
    /**
     * Halaman Utama Modul Dokumen RKPD (Coming Soon)
     */
    public function index(Request $request)
    {
        return view('rkpd.coming_soon');
    }

    /**
     * Workspace / Draft & Agregasi RKPD (Coming Soon)
     */
    public function workspace(Request $request)
    {
        return view('rkpd.coming_soon');
    }

    /**
     * Arsip Dokumen RKPD (Coming Soon)
     */
    public function archive(Request $request)
    {
        return view('rkpd.coming_soon');
    }
}
