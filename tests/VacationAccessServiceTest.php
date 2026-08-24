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

namespace {

    use OCA\AdUrlaub\Service\VacationAccessService;
    use OCA\AdUrlaub\Service\VacationSettingsService;
    use OCA\AdUrlaub\Service\VacationVisibilityPolicy;
    use OCA\LocalBase\Organization\AdOrganizationHierarchy;
    use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;
    use OCA\LocalBase\Organization\AdSuiteAdminSettingsService;
    use OCP\IGroupManager;
    use OCP\IUserManager;
    use OCP\IUserSession;

    $groups = new class implements IGroupManager {
        public function get($gid): ?object { return null; }
        public function getUserGroupIds($user): array { return []; }
        public function isAdmin($uid): bool { return false; }
        public function search($search): array { return []; }
    };
    $session = new class implements IUserSession {
        public function getUser(): ?object { return null; }
    };
    $users = new class implements IUserManager {
        public function get($uid): ?object { return null; }
    };

    $policy = new AdOrganizationPermissionPolicy(new AdOrganizationHierarchy());
    $visibility = new VacationVisibilityPolicy($policy);
    $adminSettings = (new ReflectionClass(AdSuiteAdminSettingsService::class))->newInstanceWithoutConstructor();
    $settings = new VacationSettingsService($adminSettings);
    $access = new VacationAccessService($groups, $session, $users, $policy, $visibility, $settings);

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

    echo "VacationAccessService deny-by-default tests passed\n";
}
