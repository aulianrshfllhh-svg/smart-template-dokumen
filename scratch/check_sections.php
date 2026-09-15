<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING SECTIONS FOR DOC 14 & DOC 12 ===\n";

$doc14 = App\Models\RenjaDocument::find(14);
if ($doc14) {
    echo "Doc 14 sections count: " . $doc14->sections()->count() . "\n";
    foreach ($doc14->sections as $s) {
        echo "  - Section {$s->id}: bab={$s->bab_code}, sub={$s->sub_bab_code}, title={$s->sub_bab_title}, content_len=" . strlen($s->content ?? '') . "\n";
    }
}

$doc12 = App\Models\RenjaDocument::find(12);
if ($doc12) {
    echo "\nDoc 12 effective sections count: " . $doc12->getEffectiveSections()->count() . "\n";
    foreach ($doc12->getEffectiveSections() as $s) {
        echo "  - Effective Section {$s->id}: bab={$s->bab_code}, sub={$s->sub_bab_code}, title={$s->sub_bab_title}, content_len=" . strlen($s->content ?? '') . "\n";
    }
}
