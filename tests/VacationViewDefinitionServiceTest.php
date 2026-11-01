<?php

declare(strict_types=1);

use OCA\FlzUrlaub\Service\VacationSettingsService;
use OCA\FlzUrlaub\Service\VacationViewDefinitionService;

final class FixedVacationViewSettings extends VacationSettingsService {
    public function __construct(private array $views) {}
    public function ebAreaViews(): array { return $this->views; }
}

$views = [[
    'id' => 'eb-west',
    'label' => 'EB – West',
    'roleKey' => 'eb',
    'roleGroupId' => 'flz-EB',
    'areaKey' => 'west',
    'areaGroupId' => 'flz-Bereich-West',
]];
$definitions = (new VacationViewDefinitionService(new FixedVacationViewSettings($views)))->all();
$byId = array_column($definitions, null, 'id');

if (isset($byId['eb'])) throw new RuntimeException('Die zweite, globale EB-Ansicht bleibt neben den kanonischen EB-Büroansichten bestehen.');
if (($byId['eb-west']['displayName'] ?? null) !== 'EB – West') throw new RuntimeException('Der öffentliche EB-Bürodeskriptor wird nicht verwendet.');
if (($byId['eb-west']['roles'] ?? null) !== ['flz-EB'] || ($byId['eb-west']['areas'] ?? null) !== ['flz-Bereich-West']) {
    throw new RuntimeException('EB-Büroansicht verliert ihre kanonische Rollen-/Bereichskombination.');
}
foreach (['office-west', 'pfk', 'staff'] as $required) {
    if (!isset($byId[$required])) throw new RuntimeException("Bestehende Organigrammansicht fehlt: {$required}");
}

$withoutProvider = (new VacationViewDefinitionService(new FixedVacationViewSettings([])))->all();
if (array_filter($withoutProvider, static fn(array $view): bool => str_starts_with($view['id'], 'eb-')) !== []) {
    throw new RuntimeException('Fehlender oder ungültiger Provider erzeugt dennoch EB-Büroansichten.');
}

echo "VacationViewDefinitionServiceTest: OK\n";
