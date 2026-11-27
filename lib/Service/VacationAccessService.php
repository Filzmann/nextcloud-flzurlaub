<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Zweck: Erzwingt Sichtbarkeit sowie getrennte eigene, direkte Leitungs- und Peer-Aktionsrechte.
 * Zusammenspiel: API -> VacationAccessService -> Sichtbarkeits- und Aktionspolicy sowie persönliche Adminfreigaben.
 * Vertrag: Deny by default; Organigrammzugehörigkeit allein erteilt kein Schreib- oder Genehmigungsrecht.
 */
final class VacationAccessService {
    public function __construct(
        private IGroupManager $groups,
        private IUserSession $session,
        private IUserManager $users,
        private VacationVisibilityPolicy $visibility,
        private VacationAuthorizationPolicy $authorization,
        private VacationSettingsService $settings,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
        private OrganizationDefinitionGateway $organization,
    ) {}

    public function currentUser(): ?IUser {
        return $this->session->getUser();
    }

    public function canView(): bool {
        return $this->currentUser() !== null;
    }

    public function canManage(string $employeeUid): bool {
        return $this->decide($employeeUid, false);
    }

    public function canApprove(string $employeeUid): bool {
        return $this->decide($employeeUid, true);
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
        $definition = $this->organization->definition();
        if ($definition === null) return [];
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

    private function decide(string $employeeUid, bool $approval): bool {
        $actor = $this->currentUser();
        $target = $this->users->get($employeeUid);
        if ($actor === null || $target === null) return false;

        $actorGroups = $this->groupIds($actor);
        $targetGroups = $this->groupIds($target);
        if ($this->hasFullAdminAccess($actor->getUID())) return true;

        if ($approval) {
            return $this->authorization->canApprove(
                $actor->getUID(),
                $actorGroups,
                $employeeUid,
                $targetGroups,
                $this->settings->enabledPeerApprovalGroups(),
            );
        }

        return $this->authorization->canManagePlanned(
            $actor->getUID(),
            $actorGroups,
            $employeeUid,
            $targetGroups,
            $this->settings->enabledPeerEditGroups(),
        );
    }

    private function groupIds(IUser $user): array {
        return array_map('strval', $this->groups->getUserGroupIds($user));
    }

    private function hasFullAdminAccess(string $uid): bool {
        return $this->groups->isAdmin($uid) && $this->temporaryAdminAccess->hasActiveGrant($uid);
    }

    private function definition() {
        return $this->organization->definition();
    }
}
