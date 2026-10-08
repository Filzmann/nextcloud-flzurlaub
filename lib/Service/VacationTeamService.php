<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\FlzUrlaub\Model\VacationTeam;
use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
use OCP\IGroupManager;

/**
 * Zweck: Führt Assistenzteam-Gruppen und organisatorische Fachgruppen in einem Urlaubsteam-Katalog zusammen.
 * Zusammenspiel: VacationAccessService liefert den sichtbaren Personenausschnitt; die Organisationsdefinition beschreibt dynamische Teams.
 * Vertrag: Teamzuordnung filtert nur die Ansicht; Schreibrechte bleiben im VacationAccessService.
 */
final class VacationTeamService {
    public function __construct(
        private IGroupManager $groups,
        private VacationAccessService $access,
        private ?FlzOrganizationSettingsService $organization = null,
    ) {}

    /** @return list<VacationTeam> */
    public function all(): array {
        $employees = $this->access->visibleEmployees();
        $teams = $this->asnTeams($employees);
        foreach ($this->organizationDefinitions() as $definition) {
            $members = array_values(array_filter(
                $employees,
                fn(array $employee): bool => $this->matchesOrganizationTeam($employee, $definition),
            ));
            if ($members !== []) {
                $teams[] = VacationTeam::get($definition + ['employees' => $members]);
            }
        }
        usort($teams, static function (VacationTeam $a, VacationTeam $b): int {
            $category = ($a->toArray()['category'] <=> $b->toArray()['category']);
            if ($category !== 0) return $category;

            $order = $a->sortOrder() <=> $b->sortOrder();
            return $order !== 0 ? $order : strnatcasecmp($a->displayName(), $b->displayName());
        });

        return $teams;
    }

    public function get(string $id): ?VacationTeam {
        foreach ($this->all() as $team) {
            if ($team->id() === $id) return $team;
        }

        return null;
    }

    private function asnTeams(array $employees): array {
        $employeeMap = array_column($employees, null, 'uid');
        $result = [];
        $definition = $this->definition();
        $prefix = $definition->teamGroupPrefix();
        foreach ($this->groups->search($prefix) as $group) {
            $groupId = (string)$group->getGID();
            if (!str_starts_with($groupId, $prefix) || $groupId === $prefix) continue;
            try {
                $code = $definition->normalizeTeamCode(substr($groupId, strlen($prefix)));
            } catch (\InvalidArgumentException) {
                continue;
            }
            $members = [];
            foreach ($group->getUsers() as $user) {
                if (isset($employeeMap[$user->getUID()])) {
                    $members[] = $employeeMap[$user->getUID()];
                }
            }
            if ($members === []) continue;

            usort($members, static fn(array $a, array $b): int => strnatcasecmp($a['displayName'], $b['displayName']));
            $result[] = VacationTeam::get([
                'id' => 'asn-' . $code,
                'code' => $code,
                'displayName' => $definition->teamLabelPrefix() . ' ' . $code,
                'category' => 'asn',
                'employees' => $members,
            ]);
        }

        return $result;
    }

    private function organizationDefinitions(): array {
        $definition = $this->definition();
        $result = [];
        foreach ($definition->organizationTeams() as $team) {
            $roles = array_values(array_filter(array_map($definition->roleGroupId(...), $team['roles'])));
            $areas = array_values(array_filter(array_map($definition->areaGroupId(...), $team['areas'])));
            $result[] = [
                'id' => $team['id'],
                'code' => $team['id'],
                'displayName' => $team['label'],
                'category' => 'organization',
                'sortOrder' => $team['sortOrder'],
                'areas' => $areas,
                'roles' => $roles,
            ];
        }

        return $result;
    }

    private function matchesOrganizationTeam(array $employee, array $definition): bool {
        if (array_intersect($employee['roles'], $definition['roles']) === []) return false;
        return $definition['areas'] === [] || array_intersect($employee['areas'], $definition['areas']) !== [];
    }

    private function definition(): FlzOrganizationDefinition {
        return $this->organization?->definition() ?? FlzOrganizationDefinition::defaults();
    }
}
