<?php

declare(strict_types=1);

use OCA\AdUrlaub\Service\VacationAccessService;

return [
    'uiPath' => '/index.php/apps/adurlaub/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => OCA\AdUrlaub\Service\TemporaryAdminAccessService::class,
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(VacationAccessService::class)->canApprove($uid),
];
