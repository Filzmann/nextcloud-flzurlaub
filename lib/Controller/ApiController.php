<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Controller;

use DateTimeImmutable;
use OCA\FlzUrlaub\AppInfo\Application;
use OCA\FlzUrlaub\Exception\VacationConflictException;
use OCA\FlzUrlaub\Exception\VacationOverlapException;
use OCA\FlzUrlaub\Service\IntegrationStatusService;
use OCA\FlzUrlaub\Service\HolidayCalendarService;
use OCA\FlzUrlaub\Service\VacationAccessService;
use OCA\FlzUrlaub\Service\VacationService;
use OCA\FlzUrlaub\Service\VacationTeamService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Stellt die Urlaubsplanung als schlanke JSON-API bereit.
 * Zusammenspiel: Controller -> VacationAccessService -> VacationService/VacationTeamService.
 * Vertrag: Jeder Schreibpfad prüft Sichtbarkeit und Statusrecht serverseitig; nur lesende Pfade sind CSRF-frei.
 */
final class ApiController extends Controller {
    public function __construct(
        IRequest $request,
        private VacationAccessService $access,
        private VacationService $vacations,
        private VacationTeamService $teams,
        private IntegrationStatusService $integrations,
        private HolidayCalendarService $holidays,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function week(string $start): JSONResponse {
        if (!$this->access->canView()) return $this->denied();

        try {
            $data = $this->vacations->week(new DateTimeImmutable($start), $this->access->visibleEmployees());
            $data['vacations'] = array_map(
                fn(array $vacation): array => $vacation + [
                    'canManage' => $this->access->canManageStatus($vacation['employeeUid'], $vacation['status']),
                ],
                $data['vacations'],
            );
            return new JSONResponse($data);
        } catch (\Throwable $error) {
            $this->logger->error('Urlaubswoche konnte nicht geladen werden.', ['exception' => $error]);
            return new JSONResponse(['error' => 'Die Urlaubswoche konnte nicht geladen werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function teams(): JSONResponse {
        if (!$this->access->canView()) return $this->denied();

        return new JSONResponse([
            'teams' => array_map(static fn($team): array => $team->toArray(), $this->teams->all()),
            'currentUser' => ['uid' => $this->access->currentUser()?->getUID() ?? ''],
            'integrations' => [
                'calendarConflictCheck' => $this->integrations->calendarConflictCheck(),
            ],
        ]);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function year(string $teamId, int $year): JSONResponse {
        if (!$this->access->canView()) return $this->denied();

        $team = $this->teams->get($teamId);
        if ($team === null) return new JSONResponse(['error' => 'Team nicht gefunden.'], Http::STATUS_NOT_FOUND);

        try {
            $data = $this->vacations->year($team, $year, $this->access);
            $data['holidays'] = $this->holidays->forYear($year);
            return new JSONResponse($data);
        } catch (\Throwable $error) {
            $this->logger->error('Urlaubsjahr konnte nicht geladen werden.', ['exception' => $error]);
            return new JSONResponse(['error' => 'Das Urlaubsjahr konnte nicht geladen werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function create(string $employeeUid, string $startDate, string $endDate, string $status, string $note = ''): JSONResponse {
        return $this->save(null, compact('employeeUid', 'startDate', 'endDate', 'status', 'note'));
    }

    #[NoAdminRequired]
    public function update(int $id, string $employeeUid, string $startDate, string $endDate, string $status, string $note = ''): JSONResponse {
        try {
            $existing = $this->vacations->existing($id);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Nicht gefunden.'], Http::STATUS_NOT_FOUND);
        }
        if (
            !$this->access->isVisibleEmployee($existing->employeeUid())
            || !$this->access->isVisibleEmployee($employeeUid)
            || !$this->access->canManageStatus($existing->employeeUid(), $existing->status())
            || !$this->access->canManageStatus($employeeUid, $status)
        ) {
            return $this->denied();
        }
        return $this->save($id, compact('employeeUid', 'startDate', 'endDate', 'status', 'note'));
    }

    #[NoAdminRequired]
    public function delete(int $id): JSONResponse {
        try {
            $vacation = $this->vacations->existing($id);
            if (
                !$this->access->isVisibleEmployee($vacation->employeeUid())
                || !$this->access->canManageStatus($vacation->employeeUid(), $vacation->status())
            ) {
                return $this->denied();
            }
            $this->vacations->delete($id);
            return new JSONResponse(['deleted' => true]);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Der Urlaub konnte nicht gelöscht werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function setDayStatus(string $teamId, int $year, string $employeeUid, string $date, string $status): JSONResponse {
        $team = $this->teams->get($teamId);
        if (
            $team === null
            || !$team->contains($employeeUid)
            || !preg_match('/^' . preg_quote((string)$year, '/') . '-\d{2}-\d{2}$/', $date)
        ) {
            return $this->denied();
        }

        $existing = $this->vacations->existingCovering($employeeUid, $date);
        if ($existing !== null && !$this->access->canManageStatus($employeeUid, $existing->status())) return $this->denied();
        if (!$this->access->canManageStatus($employeeUid, $status)) return $this->denied();

        try {
            return new JSONResponse([
                'id' => $this->vacations->setStatusForDate(
                    $employeeUid,
                    $date,
                    $status,
                    $this->access->currentUser()?->getUID() ?? '',
                ),
            ]);
        } catch (VacationConflictException $error) {
            return new JSONResponse(['error' => $error->getMessage(), 'conflicts' => $error->conflicts()], Http::STATUS_CONFLICT);
        } catch (VacationOverlapException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_CONFLICT);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Der Urlaubsstatus konnte nicht gespeichert werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    private function save(?int $id, array $payload): JSONResponse {
        if (
            !$this->access->isVisibleEmployee($payload['employeeUid'])
            || !$this->access->canManageStatus($payload['employeeUid'], $payload['status'])
        ) {
            return $this->denied();
        }

        try {
            return new JSONResponse([
                'id' => $this->vacations->save($payload, $id, $this->access->currentUser()?->getUID() ?? ''),
            ]);
        } catch (VacationConflictException $error) {
            return new JSONResponse(['error' => $error->getMessage(), 'conflicts' => $error->conflicts()], Http::STATUS_CONFLICT);
        } catch (VacationOverlapException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_CONFLICT);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Der Urlaub ist ungültig.'], Http::STATUS_BAD_REQUEST);
        }
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
    }
}
