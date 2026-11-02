<?php

declare(strict_types=1);


use OCA\FlzUrlaub\Service\VacationVisibilityPolicy;
use OCA\LocalBase\Organization\FlzOrganizationHierarchy;
use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;

$policy = new VacationVisibilityPolicy(new FlzOrganizationPermissionPolicy(new FlzOrganizationHierarchy()));
$canView = static fn(string $actor, bool $admin, array $actorGroups, string $target, array $targetGroups): bool => $policy->canView($actor, $admin, $actorGroups, $target, $targetGroups);

if (!$canView('admin', true, [], 'west', ['Buero', 'Bereich-West'])) throw new RuntimeException('Admin-Gesamtsicht fehlt.');
if ($canView('extern', false, [], 'extern', [])) throw new RuntimeException('Konto ohne FLZ-Mitgliedschaft erhält eine Selbstsicht.');
if (!$canView('no', false, ['Buero', 'Bereich-Nordost'], 'no-peer', ['Buero', 'Bereich-Nordost'])) throw new RuntimeException('Gemeinsame Büroansicht wird nicht erkannt.');
if (!$canView('no', false, ['Buero', 'Bereich-Nordost'], 'west', ['Buero', 'Bereich-West'])) throw new RuntimeException('Organigramm-Mitglied sieht die umschaltbare fremde Büroansicht nicht.');
if (!$canView('bl-now', false, ['BL', 'Bereich-Nordost', 'Bereich-West'], 'west', ['Buero', 'Bereich-West'])) throw new RuntimeException('Unterstelltes Büro West fehlt für BL NOW.');
if (!$canView('pdl', false, ['PDL'], 'pfk', ['PFK'])) throw new RuntimeException('Unterstellte Pflegefachkraft fehlt für PDL.');
if (!$canView('stv-pdl', false, ['StvPDL'], 'pfk', ['PFK'])) throw new RuntimeException('Unterstellte Pflegefachkraft fehlt für Stv. PDL.');
if (!$canView('stv-pdl', false, ['StvPDL'], 'pflegebuero', ['Bueroorganisation-Pflege'])) throw new RuntimeException('Büroorganisation Pflege fehlt für Stv. PDL.');
if (!$canView('gf-digi', false, ['GF-Digi'], 'fuhrpark', ['Fahrzeugverwaltung'])) throw new RuntimeException('Fahrzeugverwaltung fehlt für GF-Digi.');
if (!$canView('sekretariat', false, ['Sekretariat'], 'empfang', ['Empfang'])) throw new RuntimeException('Empfang fehlt für Sekretariat.');
if (!$canView('pfk', false, ['PFK'], 'pdl', ['PDL'])) throw new RuntimeException('Organigramm-Mitglied sieht die umschaltbare Leitungsansicht nicht.');
if (!$canView('pfk-a', false, ['PFK'], 'pfk-b', ['PFK'])) throw new RuntimeException('Gemeinsame PFK-Ansicht wird nicht erkannt.');
if (!$canView('assi', false, ['ASN-TeamA'], 'eb', ['ASN-TeamA', 'EB'])) throw new RuntimeException('Gemeinsames Assistenzteam wird nicht erkannt.');
if ($canView('assi', false, ['ASN-TeamA'], 'fremd', ['ASN-TeamB'])) throw new RuntimeException('Fremdes Assistenzteam ist sichtbar.');

echo "VacationVisibilityPolicyTest: OK\n";
