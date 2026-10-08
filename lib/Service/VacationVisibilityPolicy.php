<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;

/**
 * Zweck: Begrenzt lesbare Urlaubspersonen auf gemeinsame Ansichten, gemeinsame Assistenzteams und Unterstellungen.
 * Zusammenspiel: VacationAccessService liefert Gruppenprofile; VacationTeamService und API-Endpunkte erhalten nur den erlaubten Ausschnitt.
 * Vertrag: Admins sehen alles. Ohne eigene Organisations- oder Assistenzteam-Mitgliedschaft entsteht keine reine Selbstsicht.
 */
final class VacationVisibilityPolicy {
    public function __construct(
        private FlzOrganizationPermissionPolicy $management,
        private ?FlzOrganizationSettingsService $organization = null,
    ) {}

    public function canView(string $actorUid, bool $isAdmin, array $actorGroups, string $targetUid, array $targetGroups): bool {
        if ($isAdmin) return true;
        if (!$this->hasRelevantMembership($actorGroups)) return false;
        if ($actorUid === $targetUid) return true;
        if ($this->management->canManage($actorUid, false, $actorGroups, $targetUid, $targetGroups, [], false)) return true;
        if (array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups)) !== []) return true;
        foreach ($this->definition()->organizationTeams() as $team) {
            if ($this->belongsToOrganizationTeam($actorGroups, $team) && $this->belongsToOrganizationTeam($targetGroups, $team)) return true;
        }
        return false;
    }

    private function hasRelevantMembership(array $groups): bool {
        $definition = $this->definition();
        if (array_intersect($definition->roleGroupIds(), $groups) !== []) return true;
        return $this->asnTeamCodes($groups) !== [];
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

    private function definition(): FlzOrganizationDefinition {
        return $this->organization?->definition() ?? FlzOrganizationDefinition::defaults();
    }
}
