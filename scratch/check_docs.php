<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING DOCUMENTS IN DB ===\n";
$doc14 = App\Models\RenjaDocument::find(14);
if ($doc14) {
    echo "Doc 14:\n";
    echo "  ID: {$doc14->id}\n";
    echo "  Jenis: {$doc14->jenis_dokumen}\n";
    echo "  OPD: " . ($doc14->opd?->nama_opd ?? 'N/A') . "\n";
    echo "  TA: {$doc14->tahun_anggaran}\n";
    echo "  Status: {$doc14->status}\n";
    echo "  Source Type: {$doc14->source_type}\n";
    echo "  isLampiranPerbub: " . ($doc14->isLampiranPerbub() ? 'YES' : 'NO') . "\n";
} else {
    echo "Doc 14 not found.\n";
}

echo "\nAll Documents:\n";
foreach (App\Models\RenjaDocument::with('opd')->get() as $doc) {
    echo "  - ID {$doc->id} | OPD: " . ($doc->opd?->nama_opd ?? '-') . " | Jenis: {$doc->jenis_dokumen} | TA: {$doc->tahun_anggaran} | Status: {$doc->status} | Source: {$doc->source_type}\n";
}
