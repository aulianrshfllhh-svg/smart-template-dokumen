<?php
$dir = __DIR__ . '/../storage/app/preview_cache';
if (file_exists($dir)) {
    $files = glob($dir . '/*.pdf');
    foreach ($files as $f) {
        @unlink($f);
        echo "Deleted: " . basename($f) . "\n";
    }
}
echo "Preview cache cleared completely.\n";
