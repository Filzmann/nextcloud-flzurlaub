<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use DateTimeImmutable;
use OCA\FlzUrlaub\Model\Vacation;
use OCA\FlzUrlaub\Repository\VacationRepository;
use OCA\LocalBase\Service\FlzDemoFixtureCatalog;
use OCA\LocalBase\Service\DemoAccountProvisioningService;

/**
 * Zweck: Installiert synthetische Urlaubsfälle für jede konfigurierte FLZ-Fachgruppe.
 * Vertrag: Es werden ausschließlich registrierte Suite-Demokonten verwendet; reale Gruppenmitglieder werden nie ausgewählt.
 */
final class VacationDemoPackService {
    public function __construct(
        private DemoAccountProvisioningService $accounts,
        private FlzDemoFixtureCatalog $fixtures,
        private VacationRepository $vacations,
    ) {}

    /** @return array{accounts:array,coveredGroups:int,createdVacations:int,skippedVacations:int} */
    public function install(): array {
        $fixtures = $this->fixtures->all();
        $accounts = $this->accounts->provision('flz-full-suite-demo', $fixtures);
        $monday = new DateTimeImmutable('monday next week');
        $coveredGroups = count(array_unique(array_merge(...array_column($fixtures, 'groups'))));
        $createdVacations = 0;
        $skippedVacations = 0;
        foreach ($fixtures as $index => $fixture) {
            if ($this->vacations->existsForEmployee($fixture['uid'])) {
                $skippedVacations++;
                continue;
            }
            $day = $monday->modify('+' . ($index % 5) . ' days')->format('Y-m-d');
            $status = $index % 2 === 0 ? Vacation::STATUS_PLANNED : Vacation::STATUS_APPROVED;
            $this->vacations->save(Vacation::get([
                'employeeUid' => $fixture['uid'],
                'startDate' => $day,
                'endDate' => $day,
                'status' => $status,
                'note' => 'Neutraler Demo-Urlaub',
            ]), 'demo-seed');
            $createdVacations++;
        }
        return compact('accounts', 'coveredGroups', 'createdVacations', 'skippedVacations');
    }
}
