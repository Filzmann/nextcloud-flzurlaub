<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use InvalidArgumentException;
use OCA\LocalBase\PublicApi\V1\VacationAuthorizationSnapshot;
use OCA\LocalBase\PublicApi\V1\VacationAuthorizationSnapshotService;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Liest den öffentlichen LocalBase-V1-Vertrag für Urlaubsfreigaben und EB-Büroansichten.
 * Jeder fehlende, deaktivierte, inkompatible, ungültige oder fehlerhafte Providerzustand
 * bleibt sichtbar unterscheidbar und erteilt weder Rechte noch Ansichten.
 */
class VacationSettingsService {
    public const VALID = 'VALID';
    public const MISSING = 'MISSING';
    public const DISABLED = 'DISABLED';
    public const INCOMPATIBLE = 'INCOMPATIBLE';
    public const INVALID = 'INVALID';
    public const UNAVAILABLE = 'UNAVAILABLE';

    private const PROVIDER_APP_ID = 'localbase';
    private const SUPPORTED_CONTRACT_VERSION = '1.0';

    /** @var array{status:string,peerEditing:array<string,bool>,peerApproval:array<string,bool>,ebAreaViews:list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}>}|null */
    private ?array $resolved = null;

    public function __construct(
        private IAppManager $apps,
        private LoggerInterface $logger,
    ) {}

    public function status(): string { return $this->state()['status']; }

    /** @return array<string,bool> */
    public function peerEditing(): array { return $this->state()['peerEditing']; }

    /** @return array<string,bool> */
    public function peerApproval(): array { return $this->state()['peerApproval']; }

    /** @return list<string> */
    public function enabledPeerEditGroups(): array { return array_keys(array_filter($this->peerEditing())); }

    /** @return list<string> */
    public function enabledPeerApprovalGroups(): array { return array_keys(array_filter($this->peerApproval())); }

    /** @return list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}> */
    public function ebAreaViews(): array { return $this->state()['ebAreaViews']; }

    /** @return array{status:string,peerEditing:array<string,bool>,peerApproval:array<string,bool>,ebAreaViews:list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}>} */
    private function state(): array {
        if ($this->resolved !== null) return $this->resolved;

        try {
            if (!array_key_exists(self::PROVIDER_APP_ID, $this->apps->getAppInstalledVersions(false))) {
                return $this->resolved = $this->empty(self::MISSING);
            }
            if (!in_array(self::PROVIDER_APP_ID, $this->apps->getEnabledApps(), true)) {
                return $this->resolved = $this->empty(self::DISABLED);
            }
        } catch (\Throwable $error) {
            return $this->resolved = $this->providerFailure($error);
        }

        if (!$this->providerContractAvailable()) {
            return $this->resolved = $this->empty(self::INCOMPATIBLE);
        }

        try {
            $snapshot = $this->readProviderSnapshot();
            $contractVersion = $this->providerContractVersion($snapshot);
            $snapshot->checksum();
        } catch (InvalidArgumentException|UnexpectedValueException) {
            return $this->resolved = $this->empty(self::INCOMPATIBLE);
        } catch (\Throwable $error) {
            return $this->resolved = $this->providerFailure($error);
        }

        if ($contractVersion !== self::SUPPORTED_CONTRACT_VERSION) {
            return $this->resolved = $this->empty(self::INCOMPATIBLE);
        }
        if (!$snapshot->isValid()) {
            return $this->resolved = $this->empty(self::INVALID);
        }

        return $this->resolved = [
            'status' => self::VALID,
            'peerEditing' => $snapshot->peerEditing(),
            'peerApproval' => $snapshot->peerApproval(),
            'ebAreaViews' => $snapshot->ebAreaViews(),
        ];
    }

    protected function providerContractAvailable(): bool {
        return class_exists(VacationAuthorizationSnapshot::class)
            && class_exists(VacationAuthorizationSnapshotService::class);
    }

    protected function readProviderSnapshot(): VacationAuthorizationSnapshot {
        $provider = Server::get(VacationAuthorizationSnapshotService::class);
        if (!$provider instanceof VacationAuthorizationSnapshotService) {
            throw new UnexpectedValueException('LocalBase Vacation Authorization V1 service is not resolvable.');
        }
        return $provider->snapshot();
    }

    protected function providerContractVersion(VacationAuthorizationSnapshot $snapshot): string {
        return $snapshot->contractVersion();
    }

    /** @return array{status:string,peerEditing:array<string,bool>,peerApproval:array<string,bool>,ebAreaViews:array{}} */
    private function empty(string $status): array {
        return ['status' => $status, 'peerEditing' => [], 'peerApproval' => [], 'ebAreaViews' => []];
    }

    /** @return array{status:string,peerEditing:array<string,bool>,peerApproval:array<string,bool>,ebAreaViews:array{}} */
    private function providerFailure(\Throwable $error): array {
        $this->logger->warning('Vacation authorization snapshot unavailable', [
            'app' => 'flzurlaub',
            'provider' => self::PROVIDER_APP_ID,
            'exceptionClass' => $error::class,
        ]);
        return $this->empty(self::UNAVAILABLE);
    }
}
