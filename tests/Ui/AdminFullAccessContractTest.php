<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$technicalAdminTemplate = (string)file_get_contents($root . '/templates/admin.php');
$template = (string)file_get_contents($root . '/templates/index.php');
$script = is_file($root . '/js/admin-access.js') ? (string)file_get_contents($root . '/js/admin-access.js') : '';
$bundleOverlay = is_file($root . '/js/shared/overlay.js') ? (string)file_get_contents($root . '/js/shared/overlay.js') : '';
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$pageController=(string)file_get_contents($root.'/lib/Controller/PageController.php');
$accessController=(string)file_get_contents($root.'/lib/Controller/TemporaryAdminAccessController.php');

foreach (['flz-vacation-full-access-form','flz-vacation-full-access-enabled','flz-vacation-full-access-history','Maximal 24 Stunden'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("App-lokale DPO-Freigabesteuerung fehlt: {$contract}");
}
foreach(['Datenschutzbeauftragte','Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff'] as $contract)if(!str_contains($template,$contract))throw new RuntimeException("Sichere rollenabhängige Eintrittsmeldung fehlt: {$contract}");

// Overlay-Vertrag: Das Warn-Icon am App-Titel ist ein Button, der ein modales Overlay öffnet.
$overlayId = 'flz-vacation-admin-access-overlay';
foreach ([
    'id="' . $overlayId . '"',
    'role="dialog"',
    'aria-modal="true"',
    'flz-vacation-admin-access-overlay-close',
    'id="flz-vacation-admin-access-warning"',
    'aria-haspopup="dialog"',
    'aria-controls="' . $overlayId . '"',
    'aria-expanded',
] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Overlay-Vertrag fehlt in der Vorlage: {$contract}");
}
if (str_contains($template, '<details class="flz-vacation-admin-access-warning"')) throw new RuntimeException('Warn-Icon ist noch als aufklappbares details-Element umgesetzt.');
if (str_contains($template, 'href="#flz-vacation-full-access"')) throw new RuntimeException('Veralteter Anchor-Verweis auf die Freigabesteuerung ist noch vorhanden.');
$overlayPos = strpos($template, 'id="' . $overlayId . '"');
$sectionPos = strpos($template, 'flz-vacation-full-access-form');
$warningPos = strpos($template, 'Kein fachlicher Admin-Vollzugriff aktiv.');
if ($overlayPos === false || $sectionPos === false || $warningPos === false || $overlayPos >= $sectionPos || $overlayPos >= $warningPos) throw new RuntimeException('Warnung und Freigabesteuerung liegen nicht innerhalb des Overlays.');

foreach (['/api/admin/full-access','durationMinutes','targetUid',$overlayId] as $contract) {
    if (!str_contains($script . $routes, $contract)) throw new RuntimeException("Vollzugriffs-UI/API-Vertrag fehlt: {$contract}");
}
foreach (['AdminAccessOverlay','Overlay','Escape','aria-expanded','flz-shared-overlay','flz-shared-warn-trigger','Widerrufen','hidden'] as $contract) {
    if (!str_contains($bundleOverlay, $contract)) throw new RuntimeException("Geteiltes Overlay-Bundle fehlt: {$contract}");
}
if (!str_contains($script, 'AdminAccessOverlay')) throw new RuntimeException('admin-access.js bootstrapped nicht das geteilte AdminAccessOverlay.');
foreach (["addScript('flzurlaub', 'shared/overlay')", 'class="flz-shared-overlay"', 'class="flz-shared-warn-trigger"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Overlay-Vertrag fehlt in der Vorlage: {$contract}");
}
if (!str_contains($routes, "'verb' => 'DELETE'")) throw new RuntimeException('Widerrufroute fehlt.');
if(str_contains($technicalAdminTemplate,'flz-vacation-full-access-form'))throw new RuntimeException('Freigabesteuerung ist noch an den technischen Adminbereich gebunden.');
foreach(['canManageAdminAccess','showMissingAdminGrant'] as $contract)if(!str_contains($template,$contract)||!str_contains($pageController,"'{$contract}'"))throw new RuntimeException("Rollenabhängige Eintrittsgrenze fehlt: {$contract}");
foreach(['showAdminAccessLink'] as $contract)if(!str_contains($pageController,"'{$contract}'"))throw new RuntimeException("Rollenabhängige Eintrittsgrenze fehlt: {$contract}");
if(!str_contains($pageController,"'showAdminAccessLink' => \$canManageAdminAccess && \$showMissingAdminGrant"))throw new RuntimeException('Direktlink ist nicht auf gleichzeitige Datenschutz- und Adminrolle begrenzt.');
if(str_contains($accessController,'PublicPage'))throw new RuntimeException('Freigaberouten dürfen nicht öffentlich erreichbar sein.');

echo "Filzmann Urlaubsplanung admin full access overlay contract tests passed\n";
