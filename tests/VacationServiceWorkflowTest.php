<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventDispatcher { public function dispatchTyped(object $event): object; }
}

namespace OCP {
    interface IUser { public function getUID(): string; }
}

namespace OCA\AdUrlaub\Repository {
    use OCA\AdUrlaub\Model\Vacation;

    class VacationRepository {
        /** @var list<Vacation> */ public array $vacations = [];
        public ?Vacation $covering = null;
        public ?Vacation $saved = null;
        public ?int $deleted = null;
        public array $lastRange = [];

        public function findRange(string $start, string $end, array $uids): array {
            $this->lastRange = [$start, $end, $uids];
            return $this->vacations;
        }
        public function find(int $id): ?Vacation {
            foreach ($this->vacations as $vacation) if ($vacation->id() === $id) return $vacation;
            return null;
        }
        public function findCoveringDate(string $uid, string $date): ?Vacation { return $this->covering; }
        public function hasOverlap(string $uid, string $start, string $end, ?int $excludeId = null): bool { return false; }
        public function save(Vacation $vacation, string $actorUid): int { $this->saved = $vacation; return $vacation->id() ?? 99; }
        public function delete(int $id): void { $this->deleted = $id; }
    }
}

namespace OCA\AdUrlaub\Service {
    use OCP\IUser;

    class VacationAccessService {
        public function __construct(private ?IUser $user) {}
        public function currentUser(): ?IUser { return $this->user; }
        public function canManage(string $uid): bool { return $uid !== 'blocked'; }
        public function canApprove(string $uid): bool { return $uid === 'alice'; }
    }
}

namespace {

    use OCA\AdUrlaub\Exception\VacationConflictException;
    use OCA\AdUrlaub\Model\Vacation;
    use OCA\AdUrlaub\Model\VacationTeam;
    use OCA\AdUrlaub\Repository\VacationRepository;
    use OCA\AdUrlaub\Service\VacationAccessService;
    use OCA\AdUrlaub\Service\VacationService;
    use OCA\LocalBase\Calendar\ScheduleConflict;
    use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
    use OCP\EventDispatcher\IEventDispatcher;
    use OCP\IUser;

    $vacation = static fn(int $id, string $uid, string $from, string $to, string $status): Vacation => Vacation::get([
        'id' => $id, 'employeeUid' => $uid, 'startDate' => $from, 'endDate' => $to, 'status' => $status, 'note' => 'Test',
    ]);
    $repository = new VacationRepository();
    $repository->vacations = [
        $vacation(1, 'alice', '2026-05-01', '2026-05-03', 'planned'),
        $vacation(2, 'bob', '2026-05-02', '2026-05-02', 'approved'),
    ];
    $events = new class implements IEventDispatcher {
        public bool $conflict = false;
        public ?ScheduleConflictQueryEvent $event = null;
        public function dispatchTyped(object $event): object {
            if (!$event instanceof ScheduleConflictQueryEvent) throw new RuntimeException('Unerwartetes Event.');
            $this->event = $event;
            if ($this->conflict) $event->add(new ScheduleConflict('shift', new \DateTimeImmutable('2026-05-02 08:00 UTC'), new \DateTimeImmutable('2026-05-02 16:00 UTC'), 'Dienst'));
            return $event;
        }
    };
    $service = new VacationService($repository, $events);

    $employees = [['uid' => 'alice', 'displayName' => 'Alice'], ['uid' => 'bob', 'displayName' => 'Bob']];
    $week = $service->week(new \DateTimeImmutable('2026-05-04'), $employees);
    if ($week['end'] !== '2026-05-10' || count($week['vacations']) !== 2 || $repository->lastRange !== ['2026-05-04', '2026-05-10', ['alice', 'bob']]) {
        throw new RuntimeException('Wochenabfrage bildet Zeitraum oder Mitarbeitende nicht korrekt ab.');
    }

    $team = VacationTeam::get(['id' => 'team', 'code' => 'team', 'displayName' => 'Team', 'category' => 'organization', 'employees' => $employees]);
    $user = new class implements IUser { public function getUID(): string { return 'alice'; } };
    $year = $service->year($team, 2026, new VacationAccessService($user));
    if (count($year['days']) !== 365 || count($year['requests']) !== 2 || !$year['team']['canCoordinate']) throw new RuntimeException('Jahresansicht ist unvollständig.');
    if (!$year['assistants'][0]['isSelf'] || !$year['assistants'][0]['canApprove'] || $year['assistants'][1]['canApprove']) throw new RuntimeException('Jahresrechte sind falsch projiziert.');
    try { $service->year($team, 1900, new VacationAccessService($user)); throw new RuntimeException('Ungültiges Jahr wurde akzeptiert.'); } catch (InvalidArgumentException) {}

    if ($service->existing(1)->employeeUid() !== 'alice') throw new RuntimeException('Bestehender Urlaub wird nicht gefunden.');
    try { $service->existing(404); throw new RuntimeException('Fehlender Urlaub wurde akzeptiert.'); } catch (InvalidArgumentException) {}
    $repository->covering = $repository->vacations[0];
    if ($service->existingCovering('alice', '2026-05-02')?->id() !== 1) throw new RuntimeException('Tagessuche liefert nicht den Urlaub.');
    $service->delete(2);
    if ($repository->deleted !== 2) throw new RuntimeException('Löschung wird nicht delegiert.');

    $repository->covering = null;
    if ($service->setStatusForDate('alice', '2026-06-01', 'planned', 'manager') !== 99 || $repository->saved?->startDate() !== '2026-06-01') {
        throw new RuntimeException('Tagesstatus legt keinen neuen Urlaub an.');
    }
    $repository->covering = $repository->vacations[0];
    if ($service->setStatusForDate('alice', '2026-05-02', 'approved', 'manager') !== 1 || $events->event?->employeeUid() !== 'alice') {
        throw new RuntimeException('Genehmigung aktualisiert bestehenden Urlaub oder Konfliktabfrage nicht.');
    }
    $events->conflict = true;
    try {
        $service->save(['employeeUid' => 'alice', 'startDate' => '2026-05-02', 'endDate' => '2026-05-02', 'status' => 'approved', 'note' => ''], null, 'manager');
        throw new RuntimeException('Genehmigter Urlaub mit Dienstkonflikt wurde gespeichert.');
    } catch (VacationConflictException $error) {
        if ($error->conflicts() === []) throw new RuntimeException('Konfliktliste fehlt.');
    }

    echo "VacationServiceWorkflowTest: OK\n";
}
