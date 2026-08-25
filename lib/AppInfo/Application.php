<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\AppInfo;

use OCA\AdUrlaub\Listener\AbsenceEmployeeDiscoveryListener;
use OCA\AdUrlaub\Listener\AbsenceQueryListener;
use OCA\AdUrlaub\Listener\IntegrationCapabilityQueryListener;
use OCA\AdUrlaub\Listener\StandaloneNavigationListener;
use OCA\AdUrlaub\Privacy\VacationPrivacyProviderListener;
use OCA\AdUrlaub\Permission\NextcloudVacationPermissionSource;
use OCA\AdUrlaub\Permission\VacationPermissionProviderListener;
use OCA\AdUrlaub\Permission\VacationPermissionSourceInterface;
use OCA\AdUrlaub\Repository\TemporaryAdminAccessRepository;
use OCA\AdUrlaub\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\AdUrlaub\Service\TemporaryAdminAccessChecker;
use OCA\AdUrlaub\Service\TemporaryAdminAccessService;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent;
use OCA\LocalBase\Calendar\AbsenceQueryEvent;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
use OCA\LocalBase\Privacy\RetentionProviderRegistryEvent;
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
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, VacationPermissionProviderListener::class);
        $context->registerServiceAlias(VacationPermissionSourceInterface::class, NextcloudVacationPermissionSource::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerEventListener(RetentionProviderRegistryEvent::class, VacationPrivacyProviderListener::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
    }
    public function boot(IBootContext $context): void {}
}
