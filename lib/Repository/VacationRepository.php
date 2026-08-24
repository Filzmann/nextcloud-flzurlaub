<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Repository;

use DateTimeImmutable;
use DateTimeZone;
use OCA\AdUrlaub\Model\Vacation;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Zweck: Kapselt gebundene Zugriffe auf die app-eigene Urlaubstabelle.
 * Zusammenspiel: VacationService erzwingt Fachregeln; dieses Repository filtert, persistiert und hydriert Vacation-Modelle.
 */
final class VacationRepository {
    public function __construct(private IDBConnection $db) {}

    /** @return list<string> */
    public function findEmployeeUidsInRange(string $startDate, string $endDate): array {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('employee_uid')
            ->from('adu_vacations')
            ->where($qb->expr()->lte('start_date', $qb->createNamedParameter($endDate)))
            ->andWhere($qb->expr()->gte('end_date', $qb->createNamedParameter($startDate)))
            ->andWhere($qb->expr()->in(
                'status',
                $qb->createNamedParameter([Vacation::STATUS_PLANNED, Vacation::STATUS_APPROVED], IQueryBuilder::PARAM_STR_ARRAY),
            ))
            ->groupBy('employee_uid')
            ->orderBy('employee_uid', 'ASC');

        $employeeUids = array_values(array_unique(array_filter(
            array_map(
                static fn(mixed $employeeUid): string => trim((string)$employeeUid),
                $qb->executeQuery()->fetchFirstColumn(),
            ),
            static fn(string $employeeUid): bool => $employeeUid !== '',
        )));
        sort($employeeUids, SORT_STRING);
        return $employeeUids;
    }

    /** @return list<Vacation> */
    public function findRange(string $startDate, string $endDate, array $employeeUids): array {
        if ($employeeUids === []) return [];

        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('id', 'employee_uid', 'start_date', 'end_date', 'status', 'note')
            ->from('adu_vacations')
            ->where($qb->expr()->lte('start_date', $qb->createNamedParameter($endDate)))
            ->andWhere($qb->expr()->gte('end_date', $qb->createNamedParameter($startDate)))
            ->andWhere($qb->expr()->in(
                'employee_uid',
                $qb->createNamedParameter(array_values($employeeUids), IQueryBuilder::PARAM_STR_ARRAY),
            ))
            ->orderBy('start_date', 'ASC');

        return Vacation::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function find(int $id): ?Vacation {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('id', 'employee_uid', 'start_date', 'end_date', 'status', 'note')
            ->from('adu_vacations')
            ->where($qb->expr()->eq(
                'id',
                $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
            ));
        $row = $qb->executeQuery()->fetchAssociative();

        return $row === false ? null : Vacation::get($this->mapRow($row));
    }

    /** @return list<Vacation> */
    public function findByEmployeeUid(string $employeeUid,int $limit):array{
        $qb=$this->db->getQueryBuilder();
        $rows=$qb->select('id','employee_uid','start_date','end_date','status','note')->from('adu_vacations')
            ->where($qb->expr()->eq('employee_uid',$qb->createNamedParameter($employeeUid,IQueryBuilder::PARAM_STR)))
            ->orderBy('start_date','ASC')->setMaxResults($limit)->executeQuery()->fetchAllAssociative();
        return Vacation::get_all(array_map([$this,'mapRow'],$rows));
    }

    /** @return list<Vacation> */
    public function findEndedByEmployeeUid(string $employeeUid,string $cutoff,int $limit):array{
        $qb=$this->db->getQueryBuilder();
        $rows=$qb->select('id','employee_uid','start_date','end_date','status','note')->from('adu_vacations')
            ->where($qb->expr()->eq('employee_uid',$qb->createNamedParameter($employeeUid,IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->lte('end_date',$qb->createNamedParameter($cutoff,IQueryBuilder::PARAM_STR)))
            ->orderBy('end_date','ASC')->setMaxResults($limit)->executeQuery()->fetchAllAssociative();
        return Vacation::get_all(array_map([$this,'mapRow'],$rows));
    }

    public function findCoveringDate(string $employeeUid, string $date): ?Vacation {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('id', 'employee_uid', 'start_date', 'end_date', 'status', 'note')
            ->from('adu_vacations')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->lte('start_date', $qb->createNamedParameter($date)))
            ->andWhere($qb->expr()->gte('end_date', $qb->createNamedParameter($date)))
            ->orderBy('id', 'DESC')
            ->setMaxResults(1);
        $row = $qb->executeQuery()->fetchAssociative();

        return $row === false ? null : Vacation::get($this->mapRow($row));
    }

    public function hasOverlap(string $employeeUid, string $startDate, string $endDate, ?int $excludeId = null): bool {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('id')
            ->from('adu_vacations')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->lte('start_date', $qb->createNamedParameter($endDate)))
            ->andWhere($qb->expr()->gte('end_date', $qb->createNamedParameter($startDate)))
            ->setMaxResults(1);
        if ($excludeId !== null) {
            $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId, IQueryBuilder::PARAM_INT)));
        }

        return $qb->executeQuery()->fetchOne() !== false;
    }

    public function save(Vacation $vacation, string $actorUid): int {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $values = [
            'employee_uid' => $vacation->employeeUid(),
            'start_date' => $vacation->startDate(),
            'end_date' => $vacation->endDate(),
            'status' => $vacation->status(),
            'note' => $vacation->note(),
            'updated_at' => $now,
        ];
        $types = [
            'employee_uid' => IQueryBuilder::PARAM_STR,
            'start_date' => IQueryBuilder::PARAM_STR,
            'end_date' => IQueryBuilder::PARAM_STR,
            'status' => IQueryBuilder::PARAM_STR,
            'note' => IQueryBuilder::PARAM_STR,
            'updated_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
        ];
        $qb = $this->db->getQueryBuilder();
        $insert = $vacation->id() === null;
        if ($insert) {
            $qb->insert('adu_vacations');
            $values += [
                'created_by_uid' => $actorUid,
                'created_at' => $now,
            ];
            $types += [
                'created_by_uid' => IQueryBuilder::PARAM_STR,
                'created_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            ];
        } else {
            $qb
                ->update('adu_vacations')
                ->where($qb->expr()->eq(
                    'id',
                    $qb->createNamedParameter($vacation->id(), IQueryBuilder::PARAM_INT),
                ));
        }
        foreach ($values as $field => $value) {
            $parameter = $qb->createNamedParameter($value, $types[$field]);
            if ($insert) {
                $qb->setValue($field, $parameter);
            } else {
                $qb->set($field, $parameter);
            }
        }
        $qb->executeStatement();

        return $vacation->id() ?? $qb->getLastInsertId();
    }

    public function delete(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->delete('adu_vacations')
            ->where($qb->expr()->eq(
                'id',
                $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
            ))
            ->executeStatement();
    }

    public function existsForEmployee(string $employeeUid): bool {
        $qb = $this->db->getQueryBuilder();
        $qb
            ->select('id')
            ->from('adu_vacations')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->setMaxResults(1);

        return $qb->executeQuery()->fetchOne() !== false;
    }

    private function mapRow(array $row): array {
        return [
            'id' => (int)$row['id'],
            'employeeUid' => (string)$row['employee_uid'],
            'startDate' => (string)$row['start_date'],
            'endDate' => (string)$row['end_date'],
            'status' => (string)$row['status'],
            'note' => (string)$row['note'],
        ];
    }
}
