<?php

declare(strict_types=1);

namespace Psr\Log
{
    interface LoggerInterface
    {
        public function warning(string $message, array $context = []): void;
    }
}

namespace OCP\App
{
    interface IAppManager
    {
        public function getAppInstalledVersions(bool $includeShipped = true): array;
        public function getEnabledApps(): array;
    }
}

namespace OCP
{
    final class Server
    {
        /** @var array<string, object> */
        public static array $services = [];

        public static function get(string $class): object
        {
            if (!isset(self::$services[$class]))
            {
                throw new \RuntimeException('Service unavailable.');
            }
            return self::$services[$class];
        }
    }
}

namespace OCA\FlzOrganization\PublicApi\V1
{
    final class Contract
    {
        public static bool $compatible = true;

        public static function supports(string $version): bool
        {
            return self::$compatible && $version === '1.0';
        }
    }

    final class OrganizationSnapshot
    {
        public function __construct(
            private bool $valid,
            private array $definition,
            private string $version = '1.0',
        )
        {
        }

        public function contractVersion(): string
        {
            return $this->version;
        }

        public function isValid(): bool
        {
            return $this->valid;
        }

        public function definition(): array
        {
            return $this->definition;
        }
    }

    final class OrganizationSnapshotService
    {
        public bool $fail = false;

        public function __construct(private OrganizationSnapshot $snapshot)
        {
        }

        public function snapshot(): OrganizationSnapshot
        {
            if ($this->fail)
            {
                throw new \RuntimeException('Snapshot failed.');
            }
            return $this->snapshot;
        }
    }

    final class OrganizationAuthorizationService
    {
        public bool $valid = true;
        public bool $allow = false;
        public bool $failManagement = false;
        public bool $failPeers = false;
        /** @var array<string, list<string>> */
        public array $groups = [];

        public function canManage(
            string $actorUid,
            array $actorGroups,
            string $targetUid,
            array $targetGroups,
            string $scope,
            bool $allowSelf = true,
        ): bool
        {
            if ($this->failManagement)
            {
                throw new \RuntimeException('Authorization unavailable.');
            }
            if (!$this->valid)
            {
                return false;
            }
            if ($allowSelf && $actorUid === $targetUid)
            {
                return true;
            }
            return $this->allow;
        }

        public function peerGroups(string $scope): array
        {
            if ($this->failPeers)
            {
                throw new \RuntimeException('Peer authorization unavailable.');
            }
            return $this->valid ? ($this->groups[$scope] ?? []) : [];
        }
    }
}

namespace
{
    use OCA\FlzOrganization\PublicApi\V1\Contract;
    use OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService;
    use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshot;
    use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService;
    use OCA\FlzUrlaub\Service\OrganizationDefinitionGateway;
    use OCA\FlzUrlaub\Service\VacationSettingsService;
    use OCA\FlzOrganization\Organization\OrganizationDefinition;
    use OCP\App\IAppManager;
    use OCP\Server;
    use Psr\Log\LoggerInterface;

    final class GatewayApps implements IAppManager
    {
        public function __construct(
            private array $installed,
            private array $enabled,
        )
        {
        }

        public function getAppInstalledVersions(bool $includeShipped = true): array
        {
            return array_fill_keys($this->installed, '1.0.0');
        }

        public function getEnabledApps(): array
        {
            return $this->enabled;
        }
    }

    final class GatewayLogger implements LoggerInterface
    {
        public array $warnings = [];

        public function warning(string $message, array $context = []): void
        {
            $this->warnings[] = [$message, $context];
        }
    }

    $assert = static function (bool $condition, string $message): void
    {
        if (!$condition)
        {
            throw new RuntimeException($message);
        }
    };
    $definition = OrganizationDefinition::defaults()->toArray();
    $logger = new GatewayLogger();

    $gateway = new OrganizationDefinitionGateway(new GatewayApps([], []), $logger);
    $assert($gateway->status() === VacationSettingsService::MISSING, 'Ein fehlender Provider wird nicht sichtbar unterschieden.');
    $assert($gateway->definition() === null, 'Ein fehlender Provider liefert eine Organisationsdefinition.');

    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], []), $logger);
    $assert($gateway->status() === VacationSettingsService::DISABLED, 'Ein deaktivierter Provider wird nicht sichtbar unterschieden.');

    Contract::$compatible = false;
    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::INCOMPATIBLE, 'Eine inkompatible V1 wird nicht abgelehnt.');
    Contract::$compatible = true;

    $authorization = new OrganizationAuthorizationService();
    Server::$services = [
        OrganizationSnapshotService::class => new OrganizationSnapshotService(new OrganizationSnapshot(false, [])),
        OrganizationAuthorizationService::class => $authorization,
    ];
    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::INVALID, 'Ein ungültiger Snapshot wird nicht fail-closed behandelt.');

    Server::$services = [
        OrganizationSnapshotService::class => new OrganizationSnapshotService(new OrganizationSnapshot(true, $definition)),
        OrganizationAuthorizationService::class => $authorization,
    ];
    $authorization->valid = false;
    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::INVALID, 'Ein ungültiger Autorisierungszustand wird nicht fail-closed behandelt.');

    $authorization->valid = true;
    $authorization->allow = true;
    $authorization->groups = [
        'vacation.edit' => ['PFK'],
        'vacation.approve' => ['EB'],
    ];
    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::VALID, 'Ein gültiger V1-Provider wird nicht erkannt.');
    $assert($gateway->definition()?->roleGroupId('eb') === 'EB', 'Die öffentliche Definition wird nicht app-lokal projiziert.');
    $assert($gateway->peerGroups('vacation.edit') === ['PFK'], 'Peer-Bearbeitungsgruppen stammen nicht aus dem neuen Provider.');
    $assert($gateway->peerGroups('vacation.approve') === ['EB'], 'Peer-Genehmigungsgruppen stammen nicht aus dem neuen Provider.');
    $assert($gateway->canManage('actor', ['BL'], 'target', ['Buero'], 'vacation.edit'), 'Eine positive Providerentscheidung geht verloren.');

    $authorization->allow = false;
    $assert(!$gateway->canManage('actor', ['BL'], 'target', ['Buero'], 'vacation.edit'), 'Eine verweigerte Providerentscheidung wird lokal erweitert.');

    $authorization->failManagement = true;
    $policy = new \OCA\FlzUrlaub\Service\VacationAuthorizationPolicy($gateway);
    $assert(!$policy->canApprove('peer-a', ['PFK'], 'peer-b', ['PFK'], ['PFK']), 'Ein Autorisierungsfehler darf keine lokale Peergenehmigung erlauben.');
    $assert($gateway->status() === VacationSettingsService::UNAVAILABLE, 'Ein Autorisierungsfehler bleibt nicht sichtbar.');
    $assert(count($logger->warnings) === 1, 'Ein Autorisierungsfehler wird nicht protokolliert.');
    $authorization->failManagement = false;

    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::VALID, 'Die Sichtprobe benötigt einen gültigen Provider.');
    $authorization->failManagement = true;
    $visibility = new \OCA\FlzUrlaub\Service\VacationVisibilityPolicy($gateway);
    $assert(!$visibility->canView('actor', false, ['ASN-TeamA'], 'target', ['ASN-TeamA']), 'Ein Autorisierungsfehler darf keine lokale Teamsicht erlauben.');
    $assert(count($logger->warnings) === 2, 'Ein Sicht-Autorisierungsfehler wird nicht protokolliert.');
    $authorization->failManagement = false;

    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::VALID, 'Die neue Providerinstanz ist nicht gültig.');
    $authorization->failPeers = true;
    $assert($gateway->peerGroups('vacation.edit') === [], 'Ein Peerfehler darf keine Rechte liefern.');
    $assert($gateway->status() === VacationSettingsService::UNAVAILABLE, 'Ein Peerfehler bleibt nicht sichtbar.');
    $assert(count($logger->warnings) === 3, 'Ein Peerfehler wird nicht protokolliert.');
    $authorization->failPeers = false;

    $failingSnapshot = new OrganizationSnapshotService(new OrganizationSnapshot(true, $definition));
    $failingSnapshot->fail = true;
    Server::$services = [
        OrganizationSnapshotService::class => $failingSnapshot,
        OrganizationAuthorizationService::class => $authorization,
    ];
    $gateway = new OrganizationDefinitionGateway(new GatewayApps(['flz_organization'], ['flz_organization']), $logger);
    $assert($gateway->status() === VacationSettingsService::UNAVAILABLE, 'Ein Providerfehler wird nicht sichtbar als unverfügbar behandelt.');
    $assert($gateway->peerGroups('vacation.edit') === [], 'Ein Providerfehler liefert Peerrechte.');
    $assert(count($logger->warnings) === 4, 'Ein Providerfehler wird nicht datensparsam protokolliert.');

    echo "OrganizationDefinitionGatewayTest: OK\n";
}
