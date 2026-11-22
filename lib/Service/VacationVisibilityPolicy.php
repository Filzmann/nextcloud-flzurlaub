<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

/**
 * Zweck: Begrenzt lesbare Urlaubspersonen auf das Organigramm sowie gemeinsame Assistenzteams.
 * Zusammenspiel: VacationAccessService liefert Gruppenprofile; VacationTeamService und API-Endpunkte erhalten nur den erlaubten Ausschnitt.
 * Vertrag: Organigramm-Mitglieder sehen die umschaltbaren Organigrammansichten; AS und EB sehen gemeinsame ASN-Teams. Ohne relevante Mitgliedschaft entsteht keine Selbstsicht.
 */
final class VacationVisibilityPolicy {
    public function __construct(private OrganizationDefinitionGateway $organization) {}

    public function canView(string $actorUid, bool $isAdmin, array $actorGroups, string $targetUid, array $targetGroups): bool {
        if ($isAdmin) return true;
        if ($this->definition() === null || !$this->hasRelevantMembership($actorGroups)) return false;
        if ($actorUid === $targetUid) return true;
        if ($this->hasOrganizationRole($actorGroups) && $this->hasOrganizationRole($targetGroups)) return true;
        if ($this->organization->canManage($actorUid, $actorGroups, $targetUid, $targetGroups, 'vacation.edit', false)) return true;
        if ($this->definition() === null) return false;
        if (array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups)) !== []) return true;
        foreach ($this->definition()->organizationTeams() as $team) {
            if ($this->belongsToOrganizationTeam($actorGroups, $team) && $this->belongsToOrganizationTeam($targetGroups, $team)) return true;
        }
        return false;
    }

    private function hasRelevantMembership(array $groups): bool {
        if ($this->hasOrganizationRole($groups)) return true;
        return $this->asnTeamCodes($groups) !== [];
    }

    private function hasOrganizationRole(array $groups): bool {
        return array_intersect($this->definition()->roleGroupIds(), $groups) !== [];
    }

    /** @return list<string> */
    private function asnTeamCodes(array $groups): array {
        $definition = $this->definition();
        $prefix = $definition->teamGroupPrefix();
        $codes = [];
        foreach ($groups as $group) {
            $group = (string)$group;
            if (!str_starts_with($group, $prefix) || $group === $prefix) continue;
            try {
                $codes[] = $definition->normalizeTeamCode(substr($group, strlen($prefix)));
            } catch (\InvalidArgumentException) {
            }
        }
        return array_values(array_unique($codes));
    }

    private function belongsToOrganizationTeam(array $groups, array $team): bool {
        $definition = $this->definition();
        $roles = array_values(array_filter(array_map($definition->roleGroupId(...), $team['roles'])));
        if (array_intersect($roles, $groups) === []) return false;
        $areas = array_values(array_filter(array_map($definition->areaGroupId(...), $team['areas'])));
        return $areas === [] || array_intersect($areas, $groups) !== [];
    }

    private function definition(): ?VacationOrganizationDefinition {
        return $this->organization->definition();
    }
}
