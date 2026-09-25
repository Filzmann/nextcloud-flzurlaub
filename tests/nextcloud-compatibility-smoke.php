<?php

declare(strict_types=1);

use OCA\AdUrlaub\Service\VacationAccessService;
use OCA\AdUrlaub\Service\VacationRetentionPolicyService;

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'providerSetup' => static fn(): array => OCP\Server::get(VacationRetentionPolicyService::class)->save([
        'enabled' => true, 'reviewAfterDays' => 365, 'action' => 'REVIEW',
    ]),
    'uiPath' => '/index.php/apps/adurlaub/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => OCA\AdUrlaub\Service\TemporaryAdminAccessService::class,
    'grantManagerGroups' => static fn(): array => [OCA\AdUrlaub\Service\TemporaryAdminAccessService::GRANT_MANAGER_GROUP],
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(VacationAccessService::class)->canApprove($uid),
    'apiSmokes' => [
        ['/index.php/apps/adurlaub/api/week?start=2035-01-01', [200]],
    ],
];
