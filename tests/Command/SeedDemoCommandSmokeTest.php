<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$command = file_get_contents($root . '/lib/Command/SeedDemoCommand.php');
$service = file_get_contents($root . '/lib/Service/VacationDemoPackService.php');
$controller = file_get_contents($root . '/lib/Controller/DemoAdminController.php');
$routes = file_get_contents($root . '/appinfo/routes.php');
$info = file_get_contents($root . '/appinfo/info.xml');
$template = file_get_contents($root . '/templates/admin.php');
if (in_array(false, [$command, $service, $controller, $routes, $info, $template], true)) throw new RuntimeException('Urlaubs-Demo-Pack ist unvollständig.');
foreach (['VacationDemoPackService', '->install()'] as $contract) if (!str_contains($command, $contract)) throw new RuntimeException("Demo-Command delegiert nicht: {$contract}");
foreach (['FlzDemoFixtureCatalog', 'DemoAccountProvisioningService', "->provision('flz-full-suite-demo'", 'existsForEmployee'] as $contract) if (!str_contains($service, $contract)) throw new RuntimeException("Sicherer Urlaubs-Demo-Vertrag fehlt: {$contract}");
foreach (['IGroupManager', '->getUsers()', 'array_values('] as $unsafe) if (str_contains($command, $unsafe)) throw new RuntimeException("Urlaubs-Demo verwendet reale Gruppenmitglieder: {$unsafe}");
foreach (['/api/admin/demo-pack/install', "'verb' => 'POST'"] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Demo-Route fehlt: {$contract}");
foreach (['<admin>OCA\\FlzUrlaub\\Settings\\Admin</admin>', '<admin-section>OCA\\FlzUrlaub\\Settings\\AdminSection</admin-section>'] as $contract) if (!str_contains($info, $contract)) throw new RuntimeException("Adminregistrierung fehlt: {$contract}");
foreach (['TemporaryAdminAccessChecker', 'private function isAdmin()', '$this->groups->isAdmin(', 'hasActiveGrant(', 'Http::STATUS_FORBIDDEN'] as $contract) if (!str_contains($controller, $contract)) throw new RuntimeException("Adminschutz fehlt: {$contract}");
if (str_contains($controller, 'NoCSRFRequired')) throw new RuntimeException('Demo-Installation umgeht CSRF.');
foreach (['id="flz-vacation-demo-confirm"', 'id="flz-vacation-demo-install"', 'nicht automatisch'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Demo-Adminoberfläche fehlt: {$contract}");

echo "SeedDemoCommandSmokeTest: OK\n";
