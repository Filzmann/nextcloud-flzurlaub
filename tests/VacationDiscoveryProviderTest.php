<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace OCP\DB\QueryBuilder {
    interface IQueryBuilder {
        public const PARAM_STR = 2;
        public const PARAM_STR_ARRAY = 102;
    }
}

namespace OCP {
    interface IDBConnection { public function getQueryBuilder(): \OCP\DB\QueryBuilder\IQueryBuilder; }
}

namespace {
    use OCA\AdUrlaub\Listener\AbsenceEmployeeDiscoveryListener;
    use OCA\AdUrlaub\Listener\AbsenceQueryListener;
    use OCA\AdUrlaub\Repository\VacationRepository;
    use OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent;
    use OCA\LocalBase\Calendar\AbsenceQueryEvent;
    use OCP\DB\QueryBuilder\IQueryBuilder;
    use OCP\IDBConnection;

    $result = new class {
        public array $rows = [];
        public function fetchFirstColumn(): array { return [' bob ', 'alice', 'alice', '', '0']; }
        public function fetchAllAssociative(): array { return $this->rows; }
    };
    $expression = new class {
        public function lte(string $field, string $parameter): string { return "{$field} <= {$parameter}"; }
        public function gte(string $field, string $parameter): string { return "{$field} >= {$parameter}"; }
        public function in(string $field, string $parameter): string { return "{$field} IN {$parameter}"; }
    };
    $query = new class($expression, $result) implements IQueryBuilder {
        public array $parameters = [];
        public array $conditions = [];
        public array $selection = [];
        public array $ordering = [];
        public function __construct(private object $expression, private object $result) {}
        public function select(string ...$fields): self { $this->selection = $fields; return $this; }
        public function from(string $table): self { return $this; }
        public function where(string $condition): self { $this->conditions[] = $condition; return $this; }
        public function andWhere(string $condition): self { $this->conditions[] = $condition; return $this; }
        public function groupBy(string ...$fields): self { return $this; }
        public function orderBy(string $field, string $direction): self { $this->ordering = [$field, $direction]; return $this; }
        public function expr(): object { return $this->expression; }
        public function createNamedParameter(mixed $value, int $type = self::PARAM_STR): string {
            $this->parameters[] = [$value, $type];
            return ':p' . count($this->parameters);
        }
        public function executeQuery(): object { return $this->result; }
    };
    $db = new class($query) implements IDBConnection {
        public function __construct(private IQueryBuilder $query) {}
        public function getQueryBuilder(): IQueryBuilder { return $this->query; }
    };
    $repository = new VacationRepository($db);

    if ($repository->findEmployeeUidsInRange('2026-01-01', '2028-12-31') !== ['0', 'alice', 'bob']) {
        throw new RuntimeException('Die Repository-Discovery liefert keine normalisierte UID-Menge.');
    }
    if ($query->selection !== ['employee_uid'] || $query->ordering !== ['employee_uid', 'ASC']) {
        throw new RuntimeException('Die Repository-Discovery ist nicht minimal und deterministisch.');
    }
    if ($query->parameters !== [
        ['2028-12-31', IQueryBuilder::PARAM_STR],
        ['2026-01-01', IQueryBuilder::PARAM_STR],
        [['planned', 'approved'], IQueryBuilder::PARAM_STR_ARRAY],
    ]) {
        throw new RuntimeException('Zeitraum und zulässige Status werden nicht gebunden abgefragt.');
    }

    $query->parameters = [];
    $event = new AbsenceEmployeeDiscoveryEvent(
        new DateTimeImmutable('2026-01-01T00:00:00+01:00'),
        new DateTimeImmutable('2029-01-01T00:00:00-05:00'),
    );
    (new AbsenceEmployeeDiscoveryListener($repository))->handle($event);
    if ($event->employeeUids() !== ['0', 'alice', 'bob']) {
        throw new RuntimeException('Der AD-Urlaub-Provider beantwortet die begrenzte Discovery nicht.');
    }
    if ($query->parameters[0] !== ['2029-01-01', IQueryBuilder::PARAM_STR]
        || $query->parameters[1] !== ['2026-01-01', IQueryBuilder::PARAM_STR]) {
        throw new RuntimeException('Die Discovery verschiebt fachliche Datumsgrenzen durch abweichende Endzeitzonen.');
    }

    $query->parameters = [];
    $result->rows = [[
        'id' => 1,
        'employee_uid' => 'alice',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-01',
        'status' => 'approved',
        'note' => 'bleibt intern',
    ]];
    $queryEvent = new AbsenceQueryEvent(
        new DateTimeImmutable('2026-01-01T00:00:00+01:00'),
        new DateTimeImmutable('2029-01-01T00:00:00+01:00'),
        ['alice'],
    );
    (new AbsenceQueryListener($repository))->handle($queryEvent);
    if ($query->parameters !== [
        ['2028-12-31', IQueryBuilder::PARAM_STR],
        ['2026-01-01', IQueryBuilder::PARAM_STR],
        [['alice'], IQueryBuilder::PARAM_STR_ARRAY],
    ]) {
        throw new RuntimeException('Die ganztägige Urlaubsabfrage verschiebt fachliche Datumsgrenzen durch UTC-Konvertierung.');
    }
    $absencePayload = $queryEvent->absences()[0]?->toArray() ?? [];
    if (($absencePayload['start'] ?? '') !== '2026-01-01T00:00:00+01:00'
        || ($absencePayload['end'] ?? '') !== '2026-01-02T00:00:00+01:00'
        || array_key_exists('note', $absencePayload)) {
        throw new RuntimeException('Der Provider liefert keinen datenschutzarmen ganztägigen Urlaubsvertrag.');
    }

    $application = (string)file_get_contents(__DIR__ . '/../lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(AbsenceEmployeeDiscoveryEvent::class, AbsenceEmployeeDiscoveryListener::class)')) {
        throw new RuntimeException('Der Discovery-Provider ist nicht im Nextcloud-Bootstrap registriert.');
    }

    echo "VacationDiscoveryProviderTest: OK\n";
}
