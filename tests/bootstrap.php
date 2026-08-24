<?php

declare(strict_types=1);

require_once __DIR__ . '/../../localbase/tests/bootstrap.php';

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $mappings = [
        'OCA\\AdUrlaub\\' => $root . '/lib/',
        'OCA\\FilzmannDataProtection\\' => $root . '/tests/stubs/FilzmannDataProtection/',
    ];
    foreach ($mappings as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) continue;
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $directory . $relative . '.php';
        if (is_file($path)) require_once $path;
        return;
    }
});
