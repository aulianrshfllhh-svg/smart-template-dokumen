<?php
// Isolated audit runner: never connects to the application database or storage.
require dirname(__DIR__).'/vendor/autoload.php';
$storage = sys_get_temp_dir().'/erenja-audit-'.getmypid();
foreach (['app/private', 'app/public', 'framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $dir) {
    if (!is_dir($storage.'/'.$dir)) mkdir($storage.'/'.$dir, 0777, true);
}
foreach ([
    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
    'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync', 'LARAVEL_STORAGE_PATH' => $storage,
    'APP_CONFIG_CACHE' => $storage.'/config.php', 'APP_ROUTES_CACHE' => $storage.'/routes.php',
    'VIEW_COMPILED_PATH' => $storage.'/framework/views',
] as $key => $value) {
    putenv($key.'='.$value); $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('Audit tests require SQLite :memory:.');
}
// Build an empty, disposable schema once; RefreshDatabase then uses transactions only.
if ($kernel->call('migrate', ['--force' => true]) !== 0) {
    throw new RuntimeException($kernel->output());
}
Illuminate\Foundation\Testing\RefreshDatabaseState::$inMemoryConnections['sqlite'] = $app['db']->connection()->getPdo();
Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = true;
restore_error_handler();
restore_exception_handler();
fwrite(STDERR, "Audit storage: ".$storage.PHP_EOL);
