<?php

declare(strict_types=1);


use OCA\AdUrlaub\Service\VacationVisibilityPolicy;
use OCA\LocalBase\Organization\AdOrganizationHierarchy;
use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;

$policy = new VacationVisibilityPolicy(new AdOrganizationPermissionPolicy(new AdOrganizationHierarchy()));
$canView = static fn(string $actor, bool $admin, array $actorGroups, string $target, array $targetGroups): bool => $policy->canView($actor, $admin, $actorGroups, $target, $targetGroups);

if (!$canView('admin', true, [], 'west', ['ad-Buero', 'ad-Bereich-West'])) throw new RuntimeException('Admin-Gesamtsicht fehlt.');
if ($canView('extern', false, [], 'extern', [])) throw new RuntimeException('Konto ohne AD-Mitgliedschaft erhält eine Selbstsicht.');
if (!$canView('no', false, ['ad-Buero', 'ad-Bereich-Nordost'], 'no-peer', ['ad-Buero', 'ad-Bereich-Nordost'])) throw new RuntimeException('Gemeinsame Büroansicht wird nicht erkannt.');
if ($canView('no', false, ['ad-Buero', 'ad-Bereich-Nordost'], 'west', ['ad-Buero', 'ad-Bereich-West'])) throw new RuntimeException('Fremder Bürobereich ist sichtbar.');
if (!$canView('bl-now', false, ['ad-BL', 'ad-Bereich-Nordost', 'ad-Bereich-West'], 'west', ['ad-Buero', 'ad-Bereich-West'])) throw new RuntimeException('Unterstelltes Büro West fehlt für BL NOW.');
if (!$canView('pdl', false, ['ad-PDL'], 'pfk', ['ad-PFK'])) throw new RuntimeException('Unterstellte Pflegefachkraft fehlt für PDL.');
if (!$canView('stv-pdl', false, ['ad-StvPDL'], 'pfk', ['ad-PFK'])) throw new RuntimeException('Unterstellte Pflegefachkraft fehlt für Stv. PDL.');
if (!$canView('stv-pdl', false, ['ad-StvPDL'], 'pflegebuero', ['ad-Bueroorganisation-Pflege'])) throw new RuntimeException('Büroorganisation Pflege fehlt für Stv. PDL.');
if (!$canView('gf-digi', false, ['ad-GF-Digi'], 'fuhrpark', ['ad-Fahrzeugverwaltung'])) throw new RuntimeException('Fahrzeugverwaltung fehlt für GF-Digi.');
if (!$canView('sekretariat', false, ['ad-Sekretariat'], 'empfang', ['ad-Empfang'])) throw new RuntimeException('Empfang fehlt für Sekretariat.');
if ($canView('pfk', false, ['ad-PFK'], 'pdl', ['ad-PDL'])) throw new RuntimeException('Übergeordnete PDL ist für PFK sichtbar.');
if (!$canView('pfk-a', false, ['ad-PFK'], 'pfk-b', ['ad-PFK'])) throw new RuntimeException('Gemeinsame PFK-Ansicht wird nicht erkannt.');
if (!$canView('assi', false, ['ad-ASN-TeamA'], 'eb', ['ad-ASN-TeamA', 'ad-EB'])) throw new RuntimeException('Gemeinsames Assistenzteam wird nicht erkannt.');
if ($canView('assi', false, ['ad-ASN-TeamA'], 'fremd', ['ad-ASN-TeamB'])) throw new RuntimeException('Fremdes Assistenzteam ist sichtbar.');

echo "VacationVisibilityPolicyTest: OK\n";
