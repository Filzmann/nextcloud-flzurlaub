<?php

declare(strict_types=1);


use OCA\FlzUrlaub\Service\VacationVisibilityPolicy;
use OCA\LocalBase\Organization\FlzOrganizationHierarchy;
use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;

$policy = new VacationVisibilityPolicy(new FlzOrganizationPermissionPolicy(new FlzOrganizationHierarchy()));
$canView = static fn(string $actor, bool $admin, array $actorGroups, string $target, array $targetGroups): bool => $policy->canView($actor, $admin, $actorGroups, $target, $targetGroups);

if (!$canView('admin', true, [], 'west', ['flz-Buero', 'flz-Bereich-West'])) throw new RuntimeException('Admin-Gesamtsicht fehlt.');
if ($canView('extern', false, [], 'extern', [])) throw new RuntimeException('Konto ohne FLZ-Mitgliedschaft erhält eine Selbstsicht.');
if (!$canView('no', false, ['flz-Buero', 'flz-Bereich-Nordost'], 'no-peer', ['flz-Buero', 'flz-Bereich-Nordost'])) throw new RuntimeException('Gemeinsame Büroansicht wird nicht erkannt.');
if ($canView('no', false, ['flz-Buero', 'flz-Bereich-Nordost'], 'west', ['flz-Buero', 'flz-Bereich-West'])) throw new RuntimeException('Fremder Bürobereich ist sichtbar.');
if (!$canView('bl-now', false, ['flz-BL', 'flz-Bereich-Nordost', 'flz-Bereich-West'], 'west', ['flz-Buero', 'flz-Bereich-West'])) throw new RuntimeException('Unterstelltes Büro West fehlt für BL NOW.');
if (!$canView('pdl', false, ['flz-PDL'], 'pfk', ['flz-PFK'])) throw new RuntimeException('Unterstellte Pflegefachkraft fehlt für PDL.');
if (!$canView('stv-pdl', false, ['flz-StvPDL'], 'pfk', ['flz-PFK'])) throw new RuntimeException('Unterstellte Pflegefachkraft fehlt für Stv. PDL.');
if (!$canView('stv-pdl', false, ['flz-StvPDL'], 'pflegebuero', ['flz-Bueroorganisation-Pflege'])) throw new RuntimeException('Büroorganisation Pflege fehlt für Stv. PDL.');
if (!$canView('gf-digi', false, ['flz-GF-Digi'], 'fuhrpark', ['flz-Fahrzeugverwaltung'])) throw new RuntimeException('Fahrzeugverwaltung fehlt für GF-Digi.');
if (!$canView('sekretariat', false, ['flz-Sekretariat'], 'empfang', ['flz-Empfang'])) throw new RuntimeException('Empfang fehlt für Sekretariat.');
if ($canView('pfk', false, ['flz-PFK'], 'pdl', ['flz-PDL'])) throw new RuntimeException('Übergeordnete PDL ist für PFK sichtbar.');
if (!$canView('pfk-a', false, ['flz-PFK'], 'pfk-b', ['flz-PFK'])) throw new RuntimeException('Gemeinsame PFK-Ansicht wird nicht erkannt.');
if (!$canView('assi', false, ['flz-ASN-TeamA'], 'eb', ['flz-ASN-TeamA', 'flz-EB'])) throw new RuntimeException('Gemeinsames Assistenzteam wird nicht erkannt.');
if ($canView('assi', false, ['flz-ASN-TeamA'], 'fremd', ['flz-ASN-TeamB'])) throw new RuntimeException('Fremdes Assistenzteam ist sichtbar.');

echo "VacationVisibilityPolicyTest: OK\n";
