<?php

declare(strict_types=1);

namespace OCP {
    interface IGroupManager {
        public function get($gid);
        public function getUserGroupIds($user);
        public function isAdmin($uid);
        public function search($search);
    }
    interface IUser {
        public function getUID();
        public function getDisplayName();
    }
    interface IUserManager {
        public function get($uid);
    }
    interface IUserSession {
        public function getUser();
    }
}

namespace OCA\FlzUrlaub\Service {
    final class TemporaryAdminAccessChecker {
        public bool $active = false;
        public function hasActiveGrant(string $uid): bool { return $this->active; }
    }
    final class VacationSettingsService {
        public function enabledPeerGroups(): array { return []; }
        public function asnPeerGroup(): string { return 'flz-ASN-*'; }
    }
}

namespace {

    use OCA\FlzUrlaub\Service\VacationAccessService;
    use OCA\FlzUrlaub\Service\VacationSettingsService;
    use OCA\FlzUrlaub\Service\VacationVisibilityPolicy;
    use OCA\FlzUrlaub\Service\TemporaryAdminAccessChecker;
    use OCA\LocalBase\Organization\FlzOrganizationHierarchy;
    use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;
    use OCP\IGroupManager;
    use OCP\IUserManager;
    use OCP\IUserSession;

    $groups = new class implements IGroupManager {
        public bool $admin = false;
        public function get($gid): ?object { return null; }
        public function getUserGroupIds($user): array { return []; }
        public function isAdmin($uid): bool { return $this->admin; }
        public function search($search): array { return []; }
    };
    $session = new class implements IUserSession {
        public ?object $user = null;
        public function getUser(): ?object { return $this->user; }
    };
    $users = new class implements IUserManager {
        public ?object $user = null;
        public function get($uid): ?object { return $this->user; }
    };

    $policy = new FlzOrganizationPermissionPolicy(new FlzOrganizationHierarchy());
    $visibility = new VacationVisibilityPolicy($policy);
    $settings = new VacationSettingsService();
    $adminAccess = new TemporaryAdminAccessChecker();
    $access = new VacationAccessService($groups, $session, $users, $policy, $visibility, $settings, $adminAccess);

    $assert = static function (bool $condition, string $message): void {
        if (!$condition) throw new RuntimeException($message);
    };

    $assert($access->currentUser() === null, 'Anonymous sessions unexpectedly expose a user.');
    $assert($access->canView() === false, 'Anonymous sessions unexpectedly receive view access.');
    $assert($access->canManage('missing') === false, 'Anonymous sessions unexpectedly receive management access.');
    $assert($access->canApprove('missing') === false, 'Anonymous sessions unexpectedly receive approval access.');
    $assert($access->canManageStatus('missing', 'approved') === false, 'Anonymous sessions unexpectedly approve status changes.');
    $assert($access->canManageStatus('missing', 'requested') === false, 'Anonymous sessions unexpectedly manage status changes.');
    $assert($access->isVisibleEmployee('missing') === false, 'Missing employees unexpectedly become visible.');
    $assert($access->visibleEmployees() === [], 'Anonymous sessions unexpectedly receive an employee directory.');

    $actor = new class implements \OCP\IUser { public function getUID(): string { return 'admin'; } public function getDisplayName(): string { return 'Admin'; } };
    $target = new class implements \OCP\IUser { public function getUID(): string { return 'target'; } public function getDisplayName(): string { return 'Target'; } };
    $session->user = $actor; $users->user = $target; $groups->admin = true;
    $assert($access->canManage('target') === false, 'Native Administration erhält ohne app-lokale Freigabe Vollzugriff.');
    $assert($access->isVisibleEmployee('target') === false, 'Native Administration sieht ohne app-lokale Freigabe alle Urlaubsdaten.');
    $adminAccess->active = true;
    $assert($access->canManage('target') === true, 'Aktive app-lokale Freigabe erteilt keinen Vollzugriff.');
    $assert($access->isVisibleEmployee('target') === true, 'Aktive app-lokale Freigabe erteilt keine Vollsicht.');

    echo "VacationAccessService deny-by-default tests passed\n";
}
