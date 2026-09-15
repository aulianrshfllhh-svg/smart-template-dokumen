<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RenjaDocument;
use App\Services\DocumentPreviewService;

$doc = RenjaDocument::find(12);
if ($doc) {
    $service = app(DocumentPreviewService::class);
    $service->invalidateCache($doc->id);
    $res = $service->getOrGeneratePdf($doc, true);
    echo "GENERATE RESULT:\n";
    print_r($res);
}
