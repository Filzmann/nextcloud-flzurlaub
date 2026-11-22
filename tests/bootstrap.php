<?php

declare(strict_types=1);

$flzUrlaubRoot = dirname(__DIR__);

require_once $flzUrlaubRoot . '/vendor/flz-shared/php/vendor/autoload.php';
require_once __DIR__ . '/../../localbase/tests/bootstrap.php';

spl_autoload_register(static function (string $class) use ($flzUrlaubRoot): void {
    $mappings = [
        'OCA\\FlzUrlaub\\' => $flzUrlaubRoot . '/lib/',
        // Contract tests exercise the real optional provider through this test-only boundary.
        'OCA\\FlzOrganization\\' => dirname($flzUrlaubRoot) . '/flz_organization/lib/',
        'OCA\\FlzDataProtection\\' => $flzUrlaubRoot . '/tests/stubs/FlzDataProtection/',
    ];
    foreach ($mappings as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) continue;
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $directory . $relative . '.php';
        if (is_file($path)) require_once $path;
        return;
    }
});

require_once __DIR__ . '/support/FixedOrganizationGateway.php';
