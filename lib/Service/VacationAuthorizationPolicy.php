<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;

/**
 * Trennt fachliche Urlaubsaktionen von der bloßen Organigrammzugehörigkeit.
 * Direkte Hierarchie, zuständige EB und ausdrücklich aktivierte Peerrechte
 * werden je Aktion ausgewertet; indirekte Hierarchie erteilt kein Schreibrecht.
 */
final class VacationAuthorizationPolicy {
    public function __construct(private ?FlzOrganizationSettingsService $organization = null) {}

    public function canManagePlanned(string $actorUid, array $actorGroups, string $targetUid, array $targetGroups, array $peerGroups): bool {
        if ($actorUid === $targetUid) return true;
        return $this->isDirectSupervisor($actorGroups, $targetGroups)
            || $this->isResponsibleEb($actorGroups, $targetGroups)
            || $this->isEnabledPeer($actorGroups, $targetGroups, $peerGroups);
    }

    public function canApprove(string $actorUid, array $actorGroups, string $targetUid, array $targetGroups, array $peerGroups): bool {
        if ($actorUid === $targetUid) return false;
        return $this->isDirectSupervisor($actorGroups, $targetGroups)
            || $this->isResponsibleEb($actorGroups, $targetGroups)
            || $this->isEnabledPeer($actorGroups, $targetGroups, $peerGroups);
    }

    private function isDirectSupervisor(array $actorGroups, array $targetGroups): bool {
        $definition = $this->definition();
        $actorRoles = $definition->roleKeysForGroups($actorGroups);
        $targetRoles = $definition->roleKeysForGroups($targetGroups);
        foreach ($actorRoles as $actorRole) {
            if (array_intersect($definition->hierarchy()[$actorRole] ?? [], $targetRoles) === []) continue;
            if (!$definition->roleManagementIsAreaScoped($actorRole)) return true;
            if (array_intersect($definition->areaGroupIds(), $actorGroups, $targetGroups) !== []) return true;
        }
        return false;
    }

    private function isResponsibleEb(array $actorGroups, array $targetGroups): bool {
        $definition = $this->definition();
        $ebGroup = $definition->roleGroupId('eb');
        return $ebGroup !== null
            && in_array($ebGroup, $actorGroups, true)
            && $definition->roleKeysForGroups($targetGroups) === []
            && array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups)) !== [];
    }

    private function isEnabledPeer(array $actorGroups, array $targetGroups, array $peerGroups): bool {
        $definition = $this->definition();
        $asnWildcard = $definition->teamGroupPrefix() . '*';
        if (in_array($asnWildcard, $peerGroups, true)
            && array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups)) !== []) {
            return true;
        }
        foreach ($peerGroups as $groupId) {
            $groupId = (string)$groupId;
            if (!in_array($groupId, $actorGroups, true) || !in_array($groupId, $targetGroups, true)) continue;
            if (!$definition->roleIsAreaScopedByGroup($groupId)) return true;
            if (array_intersect($definition->areaGroupIds(), $actorGroups, $targetGroups) !== []) return true;
        }
        return false;
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

    private function definition(): FlzOrganizationDefinition {
        return $this->organization?->definition() ?? FlzOrganizationDefinition::defaults();
    }
}
