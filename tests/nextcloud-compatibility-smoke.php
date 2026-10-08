<?php

declare(strict_types=1);

use OCA\FlzUrlaub\Service\VacationAccessService;
use OCA\FlzUrlaub\Service\VacationRetentionPolicyService;

return [
    'providerRegistrations' => [
        'flz_data_protection' => [
            OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
            OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent::class,
        ],
        'flz_permission_matrix' => [
            OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'providerSetup' => static fn(): array => OCP\Server::get(VacationRetentionPolicyService::class)->save([
        'enabled' => true, 'reviewAfterDays' => 365, 'action' => 'REVIEW',
    ]),
    'uiPath' => '/index.php/apps/flzurlaub/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => OCA\FlzUrlaub\Service\TemporaryAdminAccessService::class,
    'grantManagerGroups' => static fn(): array => [OCA\FlzUrlaub\Service\TemporaryAdminAccessService::GRANT_MANAGER_GROUP],
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(VacationAccessService::class)->canApprove($uid),
    'apiSmokes' => [
        ['/index.php/apps/flzurlaub/api/week?start=2035-01-01', [200]],
    ],
];
