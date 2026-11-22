<?php

declare(strict_types=1);

namespace OCP
{
    interface IAppConfig
    {
        public function getValueString(string $appId, string $key, string $default = ''): string;
        public function setValueString(string $appId, string $key, string $value): void;
    }

    final class Server
    {
        public static array $services = [];

        public static function get(string $class): object
        {
            return self::$services[$class];
        }
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

namespace Psr\Log
{
    interface LoggerInterface
    {
        public function warning(string $message, array $context = []): void;
    }
}

namespace OCA\FlzOrganization\AppInfo
{
    final class Application
    {
        public const APP_ID = 'flz_organization';
    }
}

namespace
{
use OCA\FlzUrlaub\Service\VacationAuthorizationPolicy;
use OCA\FlzUrlaub\Tests\Support\FixedOrganizationGateway;
$policy = new VacationAuthorizationPolicy(FixedOrganizationGateway::defaults());
$planned = static fn(string $actor, array $actorGroups, string $target, array $targetGroups, array $peerGroups = []): bool
    => $policy->canManagePlanned($actor, $actorGroups, $target, $targetGroups, $peerGroups);
$approve = static fn(string $actor, array $actorGroups, string $target, array $targetGroups, array $peerGroups = []): bool
    => $policy->canApprove($actor, $actorGroups, $target, $targetGroups, $peerGroups);

if (!$planned('self', ['PFK'], 'self', ['PFK'])) throw new RuntimeException('Eigene geplante Urlaube müssen bearbeitbar bleiben.');
if ($approve('self', ['PFK'], 'self', ['PFK'])) throw new RuntimeException('Eigene Urlaube dürfen nicht selbst genehmigt werden.');
if ($approve('self', ['PFK'], 'self', ['PFK'], ['PFK'])) throw new RuntimeException('Aktivierte Peergenehmigung darf keine Selbstgenehmigung erlauben.');
if ($approve('self', ['ASN-TeamA'], 'self', ['ASN-TeamA'], ['ASN-*'])) throw new RuntimeException('Aktivierte ASN-Peergenehmigung darf keine Selbstgenehmigung erlauben.');

if (!$planned('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-West'])) throw new RuntimeException('Direkte Vorgesetzte können geplante Urlaube nicht bearbeiten.');
if (!$approve('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-West'])) throw new RuntimeException('Direkte Vorgesetzte können Urlaube nicht genehmigen.');
if ($approve('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-Sued'])) throw new RuntimeException('Bereichsfremde Unterstellte werden genehmigt.');
if ($approve('gf', ['GF-AS'], 'office', ['Buero', 'Bereich-West'])) throw new RuntimeException('Indirekte Vorgesetzte erhalten entgegen dem Direktheitsvertrag Genehmigungsrecht.');

if (!$planned('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamA'])) throw new RuntimeException('Zuständige EB kann geplanten Teamurlaub nicht bearbeiten.');
if (!$approve('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamA'])) throw new RuntimeException('Zuständige EB kann Teamurlaub nicht genehmigen.');
if ($approve('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamB'])) throw new RuntimeException('EB genehmigt Urlaub eines fremden ASN-Teams.');
if ($approve('eb-a', ['EB', 'Bereich-West', 'ASN-TeamA'], 'eb-b', ['EB', 'Bereich-West', 'ASN-TeamA'])) throw new RuntimeException('EB-Teamzuständigkeit wird unzulässig auf andere EB erweitert.');
if ($planned('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'office', ['Buero', 'Bereich-West', 'ASN-TeamA'])) throw new RuntimeException('Eine manipulierte ASN-Mitgliedschaft macht Organigramm-Mitarbeitende zu AS-Unterstellten.');

if ($planned('peer-a', ['PFK'], 'peer-b', ['PFK'])) throw new RuntimeException('Peer-Bearbeitung ist ohne ausdrückliche Berechtigung aktiv.');
if (!$planned('peer-a', ['PFK'], 'peer-b', ['PFK'], ['PFK'])) throw new RuntimeException('Ausdrücklich berechtigte Peers können geplante Urlaube nicht bearbeiten.');
if ($approve('peer-a', ['PFK'], 'peer-b', ['PFK'])) throw new RuntimeException('Peer-Genehmigung ist entgegen dem Default aktiv.');
if (!$approve('peer-a', ['PFK'], 'peer-b', ['PFK'], ['PFK'])) throw new RuntimeException('Ausdrücklich berechtigte Peers können nicht genehmigen.');
if ($approve('bo-a', ['Buero', 'Bereich-West'], 'bo-b', ['Buero', 'Bereich-Sued'], ['Buero'])) throw new RuntimeException('Bereichsfremde BO-Peers können genehmigen.');

// This contract fixture loads real provider services; the in-memory adapter replaces only Nextcloud storage.
$config = new class implements \OCP\IAppConfig
{
    private array $values = [];
    public int $writes = 0;

    public function getValueString(string $appId, string $key, string $default = ''): string
    {
        return $this->values[$appId][$key] ?? $default;
    }

    public function setValueString(string $appId, string $key, string $value): void
    {
        $this->writes++;
        $this->values[$appId][$key] = $value;
    }
};
$organization = new \OCA\FlzOrganization\Organization\OrganizationSettingsService($config);
$organization->save(\OCA\FlzOrganization\Organization\OrganizationDefinition::defaults()->toArray());
$authorizationSettings = new \OCA\FlzOrganization\Organization\AuthorizationSettingsService($config, $organization);
$authorization = new \OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService($organization, $authorizationSettings);
\OCP\Server::$services = [
    \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService::class => new \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService($organization),
    \OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService::class => $authorization,
];
$apps = new class implements \OCP\App\IAppManager
{
    public function getAppInstalledVersions(bool $includeShipped = true): array
    {
        return ['flz_organization' => '0.1.0'];
    }

    public function getEnabledApps(): array
    {
        return ['flz_organization'];
    }
};
$logger = new class implements \Psr\Log\LoggerInterface
{
    public function warning(string $message, array $context = []): void
    {
        throw new RuntimeException('Unexpected provider failure: ' . $message);
    }
};
$gateway = new \OCA\FlzUrlaub\Service\OrganizationDefinitionGateway($apps, $logger);
$realPolicy = new VacationAuthorizationPolicy($gateway);
$initialWrites = $config->writes;
if ($gateway->status() !== $gateway::VALID)
{
    throw new RuntimeException('The real organization provider must be valid for this contract test.');
}
if (!$authorization->canManage('gf', ['GF-AS'], 'office', ['Buero', 'Bereich-West'], 'vacation.approve', false))
{
    throw new RuntimeException('The real provider must expose transitive management for the regression fixture.');
}
foreach (['canManagePlanned', 'canApprove'] as $action)
{
    if ($realPolicy->$action('gf', ['GF-AS'], 'office', ['Buero', 'Bereich-West'], []))
    {
        throw new RuntimeException($action . ': indirect leadership must not grant vacation permissions through the real provider.');
    }
    if (!$realPolicy->$action('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-West'], []))
    {
        throw new RuntimeException($action . ': direct same-area leadership must remain authorized.');
    }
    if ($realPolicy->$action('bl', ['BL', 'Bereich-West'], 'office', ['Buero', 'Bereich-Sued'], []))
    {
        throw new RuntimeException($action . ': foreign-area leadership must remain denied.');
    }
    if (!$realPolicy->$action('eb', ['EB', 'Bereich-West', 'ASN-TeamA'], 'as', ['ASN-TeamA'], []))
    {
        throw new RuntimeException($action . ': responsible EB must remain authorized for its ASN team.');
    }
    if ($realPolicy->$action('peer-a', ['PFK'], 'peer-b', ['PFK'], []))
    {
        throw new RuntimeException($action . ': peer access must stay disabled by default.');
    }
    if (!$realPolicy->$action('peer-a', ['PFK'], 'peer-b', ['PFK'], ['PFK']))
    {
        throw new RuntimeException($action . ': explicitly enabled peer access must remain authorized.');
    }
}
if ($realPolicy->canApprove('self', ['PFK'], 'self', ['PFK'], ['PFK']))
{
    throw new RuntimeException('The real provider must not permit self-approval with enabled peer access.');
}
if (!$realPolicy->canManagePlanned('self', ['PFK'], 'self', ['PFK'], []))
{
    throw new RuntimeException('The real provider must preserve editing own planned vacation.');
}
if ($config->writes !== $initialWrites)
{
    throw new RuntimeException('Authorization decisions must not change provider configuration.');
}

echo "VacationAuthorizationPolicyTest: OK\n";
}
