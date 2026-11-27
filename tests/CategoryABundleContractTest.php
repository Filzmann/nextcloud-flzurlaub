<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$bundleRoot = $root . '/vendor/flz-shared';
$browserBundleRoot = $root . '/js/shared';
$composerAutoload = $bundleRoot . '/php/vendor/autoload.php';
$lockPath = $bundleRoot . '/flz-shared.lock.json';
$browserLockPath = $browserBundleRoot . '/flz-shared.lock.json';

foreach ([
    $composerAutoload,
    $bundleRoot . '/php/src/Model/ModelApiTrait.php',
    $browserBundleRoot . '/api-client.js',
    $browserBundleRoot . '/model.js',
    $browserBundleRoot . '/overlay.js',
    $browserBundleRoot . '/repository.js',
    $browserBundleRoot . '/ui.js',
    $browserBundleRoot . '/LICENSE',
    $lockPath,
    $browserLockPath,
] as $requiredPath) {
    if (!is_file($requiredPath)) {
        throw new RuntimeException('App-lokaler Kategorie-A-Bestandteil fehlt: ' . $requiredPath);
    }
}

require_once $composerAutoload;

$trait = 'OCA\\FlzUrlaub\\Vendor\\Filzmann\\Shared\\Model\\ModelApiTrait';
if (!trait_exists($trait)) {
    throw new RuntimeException('Das app-isolierte ModelApiTrait ist nicht über Composer auflösbar.');
}

$lock = json_decode((string)file_get_contents($lockPath), true, flags: JSON_THROW_ON_ERROR);
if (($lock['app_id'] ?? null) !== 'flzurlaub'
    || ($lock['app_namespace'] ?? null) !== 'FlzUrlaub'
    || ($lock['php_namespace'] ?? null) !== 'OCA\\FlzUrlaub\\Vendor\\Filzmann\\Shared'
    || ($lock['browser_global'] ?? null) !== 'window.OCA.FlzUrlaub.Shared') {
    throw new RuntimeException('Der Kategorie-A-Lock beschreibt nicht das isolierte flzurlaub-Bundle.');
}
$browserLock = json_decode((string)file_get_contents($browserLockPath), true, flags: JSON_THROW_ON_ERROR);
if (($browserLock['component'] ?? null) !== 'browser'
    || ($browserLock['app_id'] ?? null) !== 'flzurlaub'
    || ($browserLock['app_namespace'] ?? null) !== 'FlzUrlaub'
    || ($browserLock['browser_global'] ?? null) !== 'window.OCA.FlzUrlaub.Shared'
    || array_keys($browserLock['packages'] ?? []) !== ['browser']) {
    throw new RuntimeException('Der Browser-Lock beschreibt nicht ausschließlich das isolierte flzurlaub-Bundle.');
}

$vacation = (string)file_get_contents($root . '/lib/Model/Vacation.php');
$team = (string)file_get_contents($root . '/lib/Model/VacationTeam.php');
$templates = (string)file_get_contents($root . '/templates/index.php')
    . (string)file_get_contents($root . '/templates/admin.php');
$scripts = (string)file_get_contents($root . '/js/main.js')
    . (string)file_get_contents($root . '/js/admin.js')
    . (string)file_get_contents($root . '/js/admin-access.js');
$application = (string)file_get_contents($root . '/lib/AppInfo/Application.php');

if (!str_contains($application, "require_once dirname(__DIR__, 2) . '/vendor/flz-shared/php/vendor/autoload.php'")) {
    throw new RuntimeException('Der Produktionsbootstrap lädt den app-lokalen Composer-Autoloader nicht genau einmal.');
}

foreach ([$vacation, $team] as $modelSource) {
    if (!str_contains($modelSource, 'OCA\\FlzUrlaub\\Vendor\\Filzmann\\Shared\\Model\\ModelApiTrait')) {
        throw new RuntimeException('Ein Urlaubsmodell konsumiert noch nicht das app-isolierte ModelApiTrait.');
    }
    if (str_contains($modelSource, 'OCA\\LocalBase\\Model\\ModelApiTrait')) {
        throw new RuntimeException('Ein Urlaubsmodell konsumiert weiterhin das LocalBase-ModelApiTrait.');
    }
}

foreach ([
    "\\OCP\\Util::addScript('flzurlaub', 'shared/api-client')",
    "\\OCP\\Util::addScript('flzurlaub', 'shared/ui')",
    "\\OCP\\Util::addScript('flzurlaub', 'shared/overlay')",
] as $scriptRegistration) {
    if (!str_contains($templates, $scriptRegistration)) {
        throw new RuntimeException('App-lokale Kategorie-A-Skriptregistrierung fehlt: ' . $scriptRegistration);
    }
}
foreach ([
    "\\OCP\\Util::addScript('localbase', 'api/api-client')",
    "\\OCP\\Util::addScript('localbase', 'ui/ui')",
    "\\OCP\\Util::addScript('flzurlaub', 'vendor/flz-shared/api-client')",
    "\\OCP\\Util::addScript('flzurlaub', 'vendor/flz-shared/ui')",
] as $foreignRegistration) {
    if (str_contains($templates, $foreignRegistration)) {
        throw new RuntimeException('Das Template lädt weiterhin Kategorie-A-Assets aus LocalBase: ' . $foreignRegistration);
    }
}
if (!str_contains($scripts, 'window.OCA.FlzUrlaub.Shared.api.ApiClient')
    || !str_contains($scripts, 'window.OCA.FlzUrlaub.Shared.ui.Notice')
    || str_contains($scripts, 'window.LocalBase.api.ApiClient')
    || str_contains($scripts, 'window.LocalBase.ui.Notice')) {
    throw new RuntimeException('Die Urlaubs-Bootstraps verwenden nicht ausschließlich das isolierte Browser-Bundle.');
}

echo "CategoryABundleContractTest: OK\n";
