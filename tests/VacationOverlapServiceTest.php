<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    interface IEventDispatcher {}
}

namespace OCA\AdUrlaub\Repository {
    use OCA\AdUrlaub\Model\Vacation;

    class VacationRepository {
        public bool $overlap = false;
        public ?int $excludedId = null;
        public ?Vacation $saved = null;

        public function hasOverlap(string $employeeUid, string $startDate, string $endDate, ?int $excludeId = null): bool {
            $this->excludedId = $excludeId;
            return $this->overlap;
        }

        public function save(Vacation $vacation, string $actorUid): int {
            $this->saved = $vacation;
            return $vacation->id() ?? 42;
        }
    }
}

namespace {

    use OCA\AdUrlaub\Exception\VacationOverlapException;
    use OCA\AdUrlaub\Repository\VacationRepository;
    use OCA\AdUrlaub\Service\VacationService;
    use OCP\EventDispatcher\IEventDispatcher;

    $repositorySource = file_get_contents(__DIR__ . '/../lib/Repository/VacationRepository.php');
    if ($repositorySource === false) throw new RuntimeException('Urlaubs-Repository konnte nicht gelesen werden.');
    if (!str_contains($repositorySource, '$qb->getLastInsertId()') || str_contains($repositorySource, 'db->lastInsertId')) throw new RuntimeException('Urlaube verwenden nicht den modernen QueryBuilder-ID-Vertrag.');
    foreach (["lte('start_date'", "gte('end_date'", "neq('id'", 'PARAM_INT'] as $contract) {
        if (!str_contains($repositorySource, $contract)) throw new RuntimeException("Überschneidungsabfrage fehlt: {$contract}");
    }

    $repository = new VacationRepository();
    $events = new class implements IEventDispatcher {};
    $service = new VacationService($repository, $events);
    $payload = ['employeeUid'=>'admin','startDate'=>'2026-05-09','endDate'=>'2026-05-17','status'=>'planned','note'=>''];

    $repository->overlap = true;
    try {
        $service->save($payload, null, 'admin');
        throw new RuntimeException('Überlappender Urlaub wurde gespeichert.');
    } catch (VacationOverlapException $error) {
        if (!str_contains($error->getMessage(), 'überschneidet')) throw new RuntimeException('Überschneidungsfehler ist nicht verständlich.');
    }

    $repository->overlap = false;
    if ($service->save($payload, 7, 'admin') !== 7 || $repository->excludedId !== 7 || $repository->saved?->id() !== 7) {
        throw new RuntimeException('Bearbeiteter Urlaub wird nicht korrekt von seiner eigenen Überschneidungsprüfung ausgenommen.');
    }

    echo "VacationOverlapServiceTest: OK\n";
}
