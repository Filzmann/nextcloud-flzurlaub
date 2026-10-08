<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\FlzUrlaub\Exception\VacationConflictException;
use OCA\FlzUrlaub\Exception\VacationOverlapException;
use OCA\FlzUrlaub\Model\Vacation;
use OCA\FlzUrlaub\Model\VacationTeam;
use OCA\FlzUrlaub\Repository\VacationRepository;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Zweck: Orchestriert Wochen- und Jahresabfragen sowie validierte Urlaubspersistenz.
 * Zusammenspiel: ApiController -> VacationService -> VacationRepository; freigegebene Urlaube fragen Kalenderkonflikte per Event ab.
 * Vertrag: Urlaube derselben Person überlappen nie; genehmigte Zeiträume bleiben frei von blockierenden Kalendereinträgen.
 */
final class VacationService {
    public function __construct(private VacationRepository $vacations, private IEventDispatcher $events) {}

    public function week(DateTimeImmutable $start, array $employees): array {
        $end = $start->modify('+6 days');

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'employees' => $employees,
            'vacations' => array_map(
                static fn(Vacation $vacation): array => $vacation->toArray(),
                $this->vacations->findRange(
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d'),
                    array_column($employees, 'uid'),
                ),
            ),
        ];
    }

    public function year(VacationTeam $team, int $year, VacationAccessService $access): array {
        if ($year < 2000 || $year > 2100) throw new InvalidArgumentException('Ungültiges Jahr.');

        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-12-31', $year);
        $vacations = $this->vacations->findRange($start, $end, array_column($team->employees(), 'uid'));
        $days = [];
        for ($day = new DateTimeImmutable($start); $day->format('Y') === (string)$year; $day = $day->modify('+1 day')) {
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'month' => (int)$day->format('n'),
                'dayOfMonth' => (int)$day->format('j'),
                'weekday' => (int)$day->format('N'),
            ];
        }
        $requests = array_map(
            static fn(Vacation $vacation): array => [
                'id' => $vacation->id(),
                'assistantUid' => $vacation->employeeUid(),
                'employeeUid' => $vacation->employeeUid(),
                'dateFrom' => $vacation->startDate(),
                'dateTo' => $vacation->endDate(),
                'startDate' => $vacation->startDate(),
                'endDate' => $vacation->endDate(),
                'status' => $vacation->status(),
                'note' => $vacation->note(),
            ],
            $vacations,
        );
        $rows = [];
        foreach ($team->employees() as $employee) {
            $cells = [];
            foreach ($days as $day) {
                $cells[$day['date']] = ['status' => '', 'requestId' => null];
            }
            foreach ($requests as $request) {
                if ($request['employeeUid'] !== $employee['uid']) continue;

                foreach ($cells as $date => $cell) {
                    $isInsideVacation = $date >= $request['startDate'] && $date <= $request['endDate'];
                    $wouldDowngradeApproval = $cell['status'] === 'approved' && $request['status'] !== 'approved';
                    if ($isInsideVacation && !$wouldDowngradeApproval) {
                        $cells[$date] = [
                            'status' => $request['status'],
                            'requestId' => $request['id'],
                        ];
                    }
                }
            }
            $rows[] = [
                'uid' => $employee['uid'],
                'displayName' => $employee['displayName'],
                'isSelf' => $employee['uid'] === $access->currentUser()?->getUID(),
                'canManage' => $access->canManage($employee['uid']),
                'canApprove' => $access->canApprove($employee['uid']),
                'days' => $cells,
            ];
        }

        $teamData = $team->toArray();
        $teamData['assistants'] = $rows;
        $teamData['vacationAssistants'] = $rows;
        $teamData['canCoordinate'] = count(array_filter(
            $rows,
            static fn(array $row): bool => $row['canApprove'],
        )) > 0;

        return [
            'team' => $teamData,
            'year' => $year,
            'days' => $days,
            'assistants' => $rows,
            'requests' => $requests,
            'currentUser' => ['uid' => $access->currentUser()?->getUID() ?? ''],
        ];
    }

    public function existing(int $id): Vacation {
        return $this->vacations->find($id) ?? throw new InvalidArgumentException('Urlaub nicht gefunden.');
    }

    public function existingCovering(string $employeeUid, string $date): ?Vacation {
        return $this->vacations->findCoveringDate($employeeUid, $date);
    }

    public function save(array $payload, ?int $id, string $actorUid): int {
        if ($id !== null) $payload['id'] = $id;

        $vacation = Vacation::get($payload);
        if ($this->vacations->hasOverlap($vacation->employeeUid(), $vacation->startDate(), $vacation->endDate(), $vacation->id())) {
            throw new VacationOverlapException();
        }
        if ($vacation->status() === Vacation::STATUS_APPROVED) $this->assertNoScheduleConflicts($vacation);

        return $this->vacations->save($vacation, $actorUid);
    }

    public function delete(int $id): void {
        $this->vacations->delete($id);
    }

    public function setStatusForDate(string $employeeUid, string $date, string $status, string $actorUid): int {
        $existing = $this->vacations->findCoveringDate($employeeUid, $date);
        $payload = $existing?->toArray() ?? [
            'employeeUid' => $employeeUid,
            'startDate' => $date,
            'endDate' => $date,
            'note' => '',
        ];
        $payload['status'] = $status;

        return $this->save($payload, $existing?->id(), $actorUid);
    }

    private function assertNoScheduleConflicts(Vacation $vacation): void {
        $utc = new DateTimeZone('UTC');
        $start = new DateTimeImmutable($vacation->startDate() . ' 00:00:00', $utc);
        $end = (new DateTimeImmutable($vacation->endDate() . ' 00:00:00', $utc))->modify('+1 day');
        $event = new ScheduleConflictQueryEvent($vacation->employeeUid(), $start, $end);
        $this->events->dispatchTyped($event);
        $conflicts = array_map(
            static fn($item): array => $item->toArray(),
            $event->conflicts(),
        );
        if ($conflicts !== []) throw new VacationConflictException($conflicts);
    }
}
