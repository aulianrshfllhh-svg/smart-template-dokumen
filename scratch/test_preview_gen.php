<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$doc12 = App\Models\RenjaDocument::find(12);
$previewService = app(App\Services\DocumentPreviewService::class);
$result = $previewService->getOrGeneratePdf($doc12, true);

echo "=== PREVIEW GEN FOR DOC 12 ===\n";
echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "PDF Path: " . ($result['pdf_path'] ?? 'none') . "\n";
echo "File Size: " . ($result['file_size'] ?? 0) . " bytes\n";
