<?php

require 'd:/erenja-app/vendor/autoload.php';

$app = require_once 'd:/erenja-app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;

echo "=== 1. ROUTE LIST SUMMARY ===\n";
$routes = Route::getRoutes();
$authProtected = 0;
$guestRoutes = 0;
$adminRoutes = 0;
$operatorRoutes = 0;
$allRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    $methods = implode('|', $route->methods());
    $action = $route->getActionName();
    $middleware = implode(',', $route->gatherMiddleware());
    
    if (str_contains($middleware, 'auth')) {
        $authProtected++;
    } else {
        $guestRoutes++;
    }
    
    if (str_starts_with($uri, 'admin')) {
        $adminRoutes++;
    }
    if (str_starts_with($uri, 'operator') || str_starts_with($uri, 'renja-murni')) {
        $operatorRoutes++;
    }
    
    $allRoutes[] = compact('uri', 'methods', 'action', 'middleware');
}

echo "Total Routes: " . count($allRoutes) . "\n";
echo "Auth Protected: {$authProtected}\n";
echo "Public/Guest: {$guestRoutes}\n";
echo "Admin Prefixed: {$adminRoutes}\n";
echo "Operator Prefixed: {$operatorRoutes}\n";

echo "\n=== 2. AUTOFIX REFERENCES AUDIT ===\n";
// Check if AutoFix is referenced anywhere in app/
$appDir = 'd:/erenja-app/app';
$autoFixRefs = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'autofix') !== false && !str_contains($file->getPathname(), 'tests')) {
            $autoFixRefs[] = $file->getPathname();
        }
    }
}
echo "AutoFix references in app/: " . count($autoFixRefs) . "\n";
foreach ($autoFixRefs as $ref) {
    echo "  - {$ref}\n";
}

echo "\n=== 3. POLICY & AUTHORIZATION AUDIT ===\n";
$policies = Gate::policies();
echo "Registered Policies: " . json_encode($policies) . "\n";

