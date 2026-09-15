<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

echo "=== TESTING CONTROLLER BEHAVIOR FOR DOC 14 & DOC 12 ===\n";

$controller = app(App\Http\Controllers\RenjaDocumentController::class);

// Simulate logged in operator
$user = App\Models\User::where('role', 'operator')->first();
Illuminate\Support\Facades\Auth::login($user);

// 1. Call preview for Doc 12 (Lampiran Doc)
echo "\n--- 1. Preview Doc 12 (Lampiran Doc ID) ---\n";
$req12 = Request::create('/renja-documents/12/preview', 'GET');
app()->instance('request', $req12);
$view12 = $controller->preview(12);
$data12 = $view12->getData();
echo "Document ID: " . $data12['document']->id . "\n";
echo "Jenis Dokumen: " . $data12['document']->jenis_dokumen . "\n";
echo "PDF Stream URL: " . $data12['pdfStreamUrl'] . "\n";

// 2. Call preview for Doc 14 with is_lampiran=1
echo "\n--- 2. Preview Doc 14 with is_lampiran=1 ---\n";
$req14Lamp = Request::create('/documents/14/lampiran/preview?is_lampiran=1', 'GET');
app()->instance('request', $req14Lamp);
$view14Lamp = $controller->preview(14);
$data14Lamp = $view14Lamp->getData();
echo "Document ID: " . $data14Lamp['document']->id . "\n";
echo "Jenis Dokumen: " . $data14Lamp['document']->jenis_dokumen . "\n";
echo "PDF Stream URL: " . $data14Lamp['pdfStreamUrl'] . "\n";

// 3. Generate PDF for Doc 12
echo "\n--- 3. Preview PDF for Doc 12 ---\n";
$reqPdf = Request::create('/renja-documents/12/preview-pdf?is_lampiran=1', 'GET');
app()->instance('request', $reqPdf);
$pdfResp = $controller->previewPdf($reqPdf, 12);
echo "PDF Response Class: " . get_class($pdfResp) . "\n";
if (method_exists($pdfResp, 'getFile')) {
    echo "PDF File Path: " . $pdfResp->getFile()->getPathname() . "\n";
    echo "PDF File Size: " . filesize($pdfResp->getFile()->getPathname()) . " bytes\n";
}
