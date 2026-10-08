<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\AppInfo;

use OCA\FlzUrlaub\Listener\AbsenceEmployeeDiscoveryListener;
use OCA\FlzUrlaub\Listener\AbsenceQueryListener;
use OCA\FlzUrlaub\Listener\IntegrationCapabilityQueryListener;
use OCA\FlzUrlaub\Listener\StandaloneNavigationListener;
use OCA\FlzUrlaub\Privacy\VacationProcessingMetadataProviderListener;
use OCA\FlzUrlaub\Privacy\VacationPrivacyProviderListener;
use OCA\FlzUrlaub\Permission\NextcloudVacationPermissionSource;
use OCA\FlzUrlaub\Permission\VacationPermissionProviderListener;
use OCA\FlzUrlaub\Permission\VacationPermissionSourceInterface;
use OCA\FlzUrlaub\Repository\TemporaryAdminAccessRepository;
use OCA\FlzUrlaub\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\FlzUrlaub\Service\TemporaryAdminAccessChecker;
use OCA\FlzUrlaub\Service\TemporaryAdminAccessService;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent;
use OCA\LocalBase\Calendar\AbsenceQueryEvent;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** Zweck: Registriert Abwesenheits-, Capability- und Standalone-Navigationsverträge im Nextcloud-Bootstrap. */
final class Application extends App implements IBootstrap {
    public const APP_ID = AppId::VALUE;
    public function __construct(array $urlParams = []) { parent::__construct(self::APP_ID, $urlParams); }
    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(AbsenceEmployeeDiscoveryEvent::class, AbsenceEmployeeDiscoveryListener::class);
        $context->registerEventListener(AbsenceQueryEvent::class, AbsenceQueryListener::class);
        $context->registerEventListener(IntegrationCapabilityQueryEvent::class, IntegrationCapabilityQueryListener::class);
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, VacationPrivacyProviderListener::class);
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, VacationProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, VacationPermissionProviderListener::class);
        $context->registerServiceAlias(VacationPermissionSourceInterface::class, NextcloudVacationPermissionSource::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerEventListener(RegisterRetentionProvidersEvent::class, VacationPrivacyProviderListener::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
    }
    public function boot(IBootContext $context): void {}
}
