<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

/**
 * Trennt fachliche Urlaubsaktionen von der bloßen Organigrammzugehörigkeit.
 * Direkte Hierarchie, zuständige EB und ausdrücklich aktivierte Peerrechte
 * werden je Aktion ausgewertet; indirekte Hierarchie erteilt kein Schreibrecht.
 */
final class VacationAuthorizationPolicy
{
    public function __construct(private OrganizationDefinitionGateway $organization)
    {
    }

    public function canManagePlanned(string $actorUid, array $actorGroups, string $targetUid, array $targetGroups, array $peerGroups): bool
    {
        if ($this->definition() === null)
        {
            return false;
        }
        $allowed = $this->organization->canManage($actorUid, $actorGroups, $targetUid, $targetGroups, 'vacation.edit');
        return $this->organization->status() === OrganizationDefinitionGateway::VALID
            && (($allowed && ($actorUid === $targetUid || $this->isDirectSuperior($actorGroups, $targetGroups)))
                || $this->isResponsibleEb($actorGroups, $targetGroups)
                || $this->isEnabledPeer($actorGroups, $targetGroups, $peerGroups));
    }

    public function canApprove(string $actorUid, array $actorGroups, string $targetUid, array $targetGroups, array $peerGroups): bool
    {
        if ($actorUid === $targetUid)
        {
            return false;
        }
        if ($this->definition() === null)
        {
            return false;
        }
        $allowed = $this->organization->canManage($actorUid, $actorGroups, $targetUid, $targetGroups, 'vacation.approve', false);
        return $this->organization->status() === OrganizationDefinitionGateway::VALID
            && (($allowed && $this->isDirectSuperior($actorGroups, $targetGroups))
                || $this->isResponsibleEb($actorGroups, $targetGroups)
                || $this->isEnabledPeer($actorGroups, $targetGroups, $peerGroups));
    }

    private function isDirectSuperior(array $actorGroups, array $targetGroups): bool
    {
        $definition = $this->definition();
        if ($definition === null)
        {
            return false;
        }
        $targetRoles = $definition->roleKeysForGroups($targetGroups);
        foreach ($definition->roleKeysForGroups($actorGroups) as $actorRole)
        {
            // The shared provider permits transitive management; vacation grants only direct edges.
            if (array_intersect($definition->hierarchy()[$actorRole] ?? [], $targetRoles) === [])
            {
                continue;
            }
            if (!$definition->roleManagementIsAreaScoped($actorRole)
                || array_intersect($definition->areaGroupIds(), $actorGroups, $targetGroups) !== [])
            {
                return true;
            }
        }
        return false;
    }

    private function isResponsibleEb(array $actorGroups, array $targetGroups): bool
    {
        $definition = $this->definition();
        $ebGroup = $definition->roleGroupId('eb');
        return $ebGroup !== null
            && in_array($ebGroup, $actorGroups, true)
            && $definition->roleKeysForGroups($targetGroups) === []
            && array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups)) !== [];
    }

    private function isEnabledPeer(array $actorGroups, array $targetGroups, array $peerGroups): bool
    {
        $definition = $this->definition();
        if ($definition === null) return false;
        $asnWildcard = $definition->teamGroupPrefix() . '*';
        if (in_array($asnWildcard, $peerGroups, true)
            && array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups)) !== []) return true;
        foreach ($peerGroups as $groupId) {
            $groupId = (string)$groupId;
            if (!in_array($groupId, $actorGroups, true) || !in_array($groupId, $targetGroups, true)) continue;
            if (!$definition->roleIsAreaScopedByGroup($groupId)
                || array_intersect($definition->areaGroupIds(), $actorGroups, $targetGroups) !== []) return true;
        }
        return false;
    }

    /** @return list<string> */
    private function asnTeamCodes(array $groups): array
    {
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

    private function definition(): ?VacationOrganizationDefinition
    {
        return $this->organization->definition();
    }
}
