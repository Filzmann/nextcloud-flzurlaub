<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Zweck: Erzwingt Mitgliedschafts-/Hierarchiesicht sowie eigene, Leitungs- und freigeschaltete Peer-Schreibrechte.
 * Zusammenspiel: API -> VacationAccessService -> zentrale Organisationspolicy, Sichtbarkeitspolicy und persönliche Freigaben.
 * Vertrag: Deny by default; Selbst-, Peer-, Team- und Leitungsrechte werden ausschließlich aus dem aktuellen Konto abgeleitet.
 */
final class VacationAccessService {
    public function __construct(
        private IGroupManager $groups,
        private IUserSession $session,
        private IUserManager $users,
        private AdOrganizationPermissionPolicy $policy,
        private VacationVisibilityPolicy $visibility,
        private VacationSettingsService $settings,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
        private ?AdOrganizationSettingsService $organization = null,
    ) {}

    public function currentUser(): ?IUser {
        return $this->session->getUser();
    }

    public function canView(): bool {
        return $this->currentUser() !== null;
    }

    public function canManage(string $employeeUid): bool {
        return $this->decide($employeeUid, true);
    }

    public function canApprove(string $employeeUid): bool {
        return $this->decide($employeeUid, false);
    }

    public function canManageStatus(string $employeeUid, string $status): bool {
        return $status === 'approved'
            ? $this->canApprove($employeeUid)
            : $this->canManage($employeeUid);
    }

    public function isVisibleEmployee(string $employeeUid): bool {
        $actor = $this->currentUser();
        $target = $this->users->get($employeeUid);
        if ($actor === null || $target === null) return false;

        return $this->visibility->canView(
            $actor->getUID(),
            $this->hasFullAdminAccess($actor->getUID()),
            $this->groupIds($actor),
            $target->getUID(),
            $this->groupIds($target),
        );
    }

    /** @return list<array{uid:string,displayName:string,roles:list<string>,areas:list<string>,canManage:bool,canApprove:bool}> */
    public function visibleEmployees(): array {
        $actor = $this->currentUser();
        if ($actor === null) return [];
        $actorGroups = $this->groupIds($actor);
        $isAdmin = $this->hasFullAdminAccess($actor->getUID());
        $users = [];
        $definition = $this->definition();
        $roleGroups = $definition->roleGroupIds();
        foreach ($roleGroups as $role) {
            foreach ($this->groups->get($role)?->getUsers() ?? [] as $user) {
                $users[$user->getUID()] = $user;
            }
        }

        $teamPrefix = $definition->teamGroupPrefix();
        foreach ($this->groups->search($teamPrefix) as $group) {
            $groupId = (string)$group->getGID();
            if (!str_starts_with($groupId, $teamPrefix) || $groupId === $teamPrefix) continue;
            try {
                $definition->normalizeTeamCode(substr($groupId, strlen($teamPrefix)));
            } catch (\InvalidArgumentException) {
                continue;
            }
            foreach ($group->getUsers() as $user) {
                $users[$user->getUID()] = $user;
            }
        }

        $result = [];
        foreach ($users as $user) {
            $ids = array_map('strval', $this->groups->getUserGroupIds($user));
            if (!$this->visibility->canView($actor->getUID(), $isAdmin, $actorGroups, $user->getUID(), $ids)) continue;

            $roles = array_values(array_intersect($roleGroups, $ids));
            $hasAreaRole = array_filter($roles, $definition->roleIsAreaScopedByGroup(...)) !== [];
            $areas = $hasAreaRole ? array_values(array_intersect($definition->areaGroupIds(), $ids)) : [];
            $result[] = [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName(),
                'roles' => $roles,
                'areas' => $areas,
                'canManage' => $this->canManage($user->getUID()),
                'canApprove' => $this->canApprove($user->getUID()),
            ];
        }
        usort($result, static fn(array $a, array $b): int => strnatcasecmp($a['displayName'], $b['displayName']));

        return $result;
    }

    private function decide(string $employeeUid, bool $allowSelf): bool {
        $actor = $this->currentUser();
        $target = $this->users->get($employeeUid);
        if ($actor === null || $target === null) return false;

        $actorGroups = $this->groupIds($actor);
        $targetGroups = $this->groupIds($target);
        if ($this->policy->canManage(
            $actor->getUID(),
            $this->hasFullAdminAccess($actor->getUID()),
            $actorGroups,
            $employeeUid,
            $targetGroups,
            $this->settings->enabledPeerGroups(),
            $allowSelf,
        )) {
            return true;
        }
        if ($actor->getUID() === $employeeUid) return false;

        $sharedAsnTeams = array_intersect($this->asnTeamCodes($actorGroups), $this->asnTeamCodes($targetGroups));
        if ($sharedAsnTeams === []) return false;
        if (in_array($this->definition()->roleGroupId('eb'), $actorGroups, true)) return true;

        return in_array($this->settings->asnPeerGroup(), $this->settings->enabledPeerGroups(), true);
    }

    private function groupIds(IUser $user): array {
        return array_map('strval', $this->groups->getUserGroupIds($user));
    }

    private function hasFullAdminAccess(string $uid): bool {
        return $this->groups->isAdmin($uid) && $this->temporaryAdminAccess->hasActiveGrant($uid);
    }

    private function asnTeamCodes(array $groupIds): array {
        $codes = [];
        $definition = $this->definition();
        $prefix = $definition->teamGroupPrefix();
        foreach ($groupIds as $groupId) {
            if (!str_starts_with($groupId, $prefix) || $groupId === $prefix) continue;
            try {
                $codes[] = $definition->normalizeTeamCode(substr($groupId, strlen($prefix)));
            } catch (\InvalidArgumentException) {
            }
        }

        return array_values(array_unique($codes));
    }

    private function definition(): AdOrganizationDefinition {
        return $this->organization?->definition() ?? AdOrganizationDefinition::defaults();
    }
}
