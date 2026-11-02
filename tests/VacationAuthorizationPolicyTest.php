<?php

declare(strict_types=1);

use OCA\FlzUrlaub\Service\VacationAuthorizationPolicy;
$policy = new VacationAuthorizationPolicy();
$planned = static fn(string $actor, array $actorGroups, string $target, array $targetGroups, array $peerGroups = []): bool
    => $policy->canManagePlanned($actor, $actorGroups, $target, $targetGroups, $peerGroups);
$approve = static fn(string $actor, array $actorGroups, string $target, array $targetGroups, array $peerGroups = []): bool
    => $policy->canApprove($actor, $actorGroups, $target, $targetGroups, $peerGroups);

if (!$planned('self', ['PFK'], 'self', ['PFK'])) throw new RuntimeException('Eigene geplante Urlaube müssen bearbeitbar bleiben.');
if ($approve('self', ['PFK'], 'self', ['PFK'])) throw new RuntimeException('Eigene Urlaube dürfen nicht selbst genehmigt werden.');

if (!$planned('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-West'])) throw new RuntimeException('Direkte Vorgesetzte können geplante Urlaube nicht bearbeiten.');
if (!$approve('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-West'])) throw new RuntimeException('Direkte Vorgesetzte können Urlaube nicht genehmigen.');
if ($approve('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-Sued'])) throw new RuntimeException('Bereichsfremde Unterstellte werden genehmigt.');
if ($approve('gf', ['GF-AS'], 'office', ['Buero', 'Bereich-West'])) throw new RuntimeException('Indirekte Vorgesetzte erhalten entgegen dem Direktheitsvertrag Genehmigungsrecht.');

if (!$planned('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamA'])) throw new RuntimeException('Zuständige EB kann geplanten Teamurlaub nicht bearbeiten.');
if (!$approve('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamA'])) throw new RuntimeException('Zuständige EB kann Teamurlaub nicht genehmigen.');
if ($approve('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamB'])) throw new RuntimeException('EB genehmigt Urlaub eines fremden ASN-Teams.');
if ($approve('eb-a', ['EB', 'Bereich-West', 'ASN-TeamA'], 'eb-b', ['EB', 'Bereich-West', 'ASN-TeamA'])) throw new RuntimeException('EB-Teamzuständigkeit wird unzulässig auf andere EB erweitert.');
if ($planned('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'office', ['Buero', 'Bereich-West', 'ASN-TeamA'])) throw new RuntimeException('Eine manipulierte ASN-Mitgliedschaft macht Organigramm-Mitarbeitende zu AS-Unterstellten.');

if ($planned('peer-a', ['PFK'], 'peer-b', ['PFK'])) throw new RuntimeException('Peer-Bearbeitung ist ohne ausdrückliche Berechtigung aktiv.');
if (!$planned('peer-a', ['PFK'], 'peer-b', ['PFK'], ['PFK'])) throw new RuntimeException('Ausdrücklich berechtigte Peers können geplante Urlaube nicht bearbeiten.');
if ($approve('peer-a', ['PFK'], 'peer-b', ['PFK'])) throw new RuntimeException('Peer-Genehmigung ist entgegen dem Default aktiv.');
if (!$approve('peer-a', ['PFK'], 'peer-b', ['PFK'], ['PFK'])) throw new RuntimeException('Ausdrücklich berechtigte Peers können nicht genehmigen.');
if ($approve('bo-a', ['Buero', 'Bereich-West'], 'bo-b', ['Buero', 'Bereich-Sued'], ['Buero'])) throw new RuntimeException('Bereichsfremde BO-Peers können genehmigen.');

echo "VacationAuthorizationPolicyTest: OK\n";
