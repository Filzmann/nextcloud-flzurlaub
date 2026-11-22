<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use InvalidArgumentException;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

/** Fail-closed consumer of the public flz_organization V1 contract. */
class OrganizationDefinitionGateway
{
    public const VALID = 'VALID';
    public const MISSING = 'MISSING';
    public const DISABLED = 'DISABLED';
    public const INCOMPATIBLE = 'INCOMPATIBLE';
    public const INVALID = 'INVALID';
    public const UNAVAILABLE = 'UNAVAILABLE';

    private const FLZ_ORGANIZATION_APP_ID = 'flz_organization';
    private const CONTRACT_VERSION = '1.0';
    private const CONTRACT_CLASS = 'OCA\\FlzOrganization\\PublicApi\\V1\\Contract';
    private const SNAPSHOT_CLASS = 'OCA\\FlzOrganization\\PublicApi\\V1\\OrganizationSnapshotService';
    private const AUTHORIZATION_CLASS = 'OCA\\FlzOrganization\\PublicApi\\V1\\OrganizationAuthorizationService';
    private const AUTHORIZATION_PROBE_UID = '__flzurlaub_contract_probe__';
    private const VACATION_EDIT_SCOPE = 'vacation.edit';

    private bool $resolved = false;
    private string $status = self::UNAVAILABLE;
    private ?VacationOrganizationDefinition $definition = null;
    private ?object $authorization = null;

    public function __construct(
        private IAppManager $apps,
        private LoggerInterface $logger,
    )
    {
    }

    public function status(): string
    {
        $this->resolve();
        return $this->status;
    }

    public function definition(): ?VacationOrganizationDefinition
    {
        $this->resolve();
        return $this->definition;
    }

    public function definitionArray(): ?array
    {
        return $this->definition()?->toArray();
    }

    public function canManage(
        string $actorUid,
        array $actorGroups,
        string $targetUid,
        array $targetGroups,
        string $scope,
        bool $allowSelf = true,
    ): bool
    {
        $this->resolve();
        if ($this->status !== self::VALID
            || !$this->authorization instanceof \OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService)
        {
            return false;
        }
        try
        {
            return $this->authorization->canManage(
                $actorUid,
                $actorGroups,
                $targetUid,
                $targetGroups,
                $scope,
                $allowSelf,
            );
        }
        catch (\Throwable $error)
        {
            $this->providerFailure($error);
            return false;
        }
    }

    /** @return list<string> */
    public function peerGroups(string $scope): array
    {
        $this->resolve();
        if ($this->status !== self::VALID
            || !$this->authorization instanceof \OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService)
        {
            return [];
        }
        try
        {
            return array_values(array_map('strval', $this->authorization->peerGroups($scope)));
        }
        catch (\Throwable $error)
        {
            $this->providerFailure($error);
            return [];
        }
    }

    private function resolve(): void
    {
        if ($this->resolved)
        {
            return;
        }
        $this->resolved = true;

        try
        {
            $installed = $this->apps->getAppInstalledVersions(false);
            if (!array_key_exists(self::FLZ_ORGANIZATION_APP_ID, $installed))
            {
                $this->status = self::MISSING;
                return;
            }
            if (!in_array(self::FLZ_ORGANIZATION_APP_ID, $this->apps->getEnabledApps(), true))
            {
                $this->status = self::DISABLED;
                return;
            }
        }
        catch (\Throwable $error)
        {
            $this->providerFailure($error);
            return;
        }

        if (!class_exists(self::CONTRACT_CLASS)
            || !class_exists(self::SNAPSHOT_CLASS)
            || !class_exists(self::AUTHORIZATION_CLASS)
            || !\OCA\FlzOrganization\PublicApi\V1\Contract::supports(self::CONTRACT_VERSION))
        {
            $this->status = self::INCOMPATIBLE;
            return;
        }

        try
        {
            $snapshotService = Server::get(self::SNAPSHOT_CLASS);
            $authorization = Server::get(self::AUTHORIZATION_CLASS);
            if (!$snapshotService instanceof \OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService
                || !$authorization instanceof \OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService)
            {
                $this->status = self::UNAVAILABLE;
                return;
            }

            $snapshot = $snapshotService->snapshot();
            if ($snapshot->contractVersion() !== self::CONTRACT_VERSION || !$snapshot->isValid())
            {
                $this->status = self::INVALID;
                return;
            }
            $definition = VacationOrganizationDefinition::fromArray($snapshot->definition());
            if (!$authorization->canManage(
                self::AUTHORIZATION_PROBE_UID,
                [],
                self::AUTHORIZATION_PROBE_UID,
                [],
                self::VACATION_EDIT_SCOPE,
            ))
            {
                $this->status = self::INVALID;
                return;
            }

            $this->definition = $definition;
            $this->authorization = $authorization;
            $this->status = self::VALID;
        }
        catch (InvalidArgumentException)
        {
            $this->status = self::INVALID;
        }
        catch (\Throwable $error)
        {
            $this->providerFailure($error);
        }
    }

    private function providerFailure(\Throwable $error): void
    {
        $this->status = self::UNAVAILABLE;
        $this->definition = null;
        $this->authorization = null;
        $this->logger->warning('Organization provider unavailable', [
            'app' => 'flzurlaub',
            'provider' => self::FLZ_ORGANIZATION_APP_ID,
            'exceptionClass' => $error::class,
        ]);
    }
}
