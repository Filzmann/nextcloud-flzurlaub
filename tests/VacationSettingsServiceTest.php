<?php

declare(strict_types=1);

namespace OCP\App {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager {
            public function getAppInstalledVersions(bool $onlyEnabled = false): array;
            public function getInstalledApps(): array;
            public function getEnabledApps(): array;
        }
    }
}

namespace OCP {
    if (!class_exists(Server::class)) {
        final class Server {
            public static mixed $service = null;
            public static function get(string $name): mixed { return self::$service; }
        }
    }
}

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface {
            public function emergency($message, array $context = []): void;
            public function alert($message, array $context = []): void;
            public function critical($message, array $context = []): void;
            public function error($message, array $context = []): void;
            public function warning($message, array $context = []): void;
            public function notice($message, array $context = []): void;
            public function info($message, array $context = []): void;
            public function debug($message, array $context = []): void;
            public function log($level, $message, array $context = []): void;
        }
    }
}

namespace {
    use OCA\FlzUrlaub\Service\VacationSettingsService;
    use OCA\LocalBase\PublicApi\V1\VacationAuthorizationSnapshot;
    use OCP\App\IAppManager;
    use Psr\Log\LoggerInterface;

    final class VacationSettingsApps implements IAppManager {
        public function __construct(private array $installed, private array $enabled) {}
        public function getAppInstalledVersions(bool $onlyEnabled = false): array {
            return array_fill_keys($onlyEnabled ? $this->enabled : $this->installed, '1.0.0');
        }
        public function getInstalledApps(): array { return $this->enabled; }
        public function getEnabledApps(): array { return $this->enabled; }
    }

    final class VacationSettingsLogger implements LoggerInterface {
        public array $warnings = [];
        public function emergency($message, array $context = []): void {}
        public function alert($message, array $context = []): void {}
        public function critical($message, array $context = []): void {}
        public function error($message, array $context = []): void {}
        public function warning($message, array $context = []): void { $this->warnings[] = [$message, $context]; }
        public function notice($message, array $context = []): void {}
        public function info($message, array $context = []): void {}
        public function debug($message, array $context = []): void {}
        public function log($level, $message, array $context = []): void {}
    }

    final class VacationSettingsUnderTest extends VacationSettingsService {
        public function __construct(
            IAppManager $apps,
            VacationSettingsLogger $logger,
            private bool $contractAvailable,
            private VacationAuthorizationSnapshot|\Throwable|null $providerSnapshot,
            private string $contractVersion = VacationAuthorizationSnapshot::CONTRACT_VERSION,
        ) {
            parent::__construct($apps, $logger);
        }

        protected function providerContractAvailable(): bool { return $this->contractAvailable; }
        protected function readProviderSnapshot(): VacationAuthorizationSnapshot {
            if ($this->providerSnapshot instanceof \Throwable) throw $this->providerSnapshot;
            if ($this->providerSnapshot === null) throw new \UnexpectedValueException('synthetic incompatible provider');
            return $this->providerSnapshot;
        }
        protected function providerContractVersion(VacationAuthorizationSnapshot $snapshot): string {
            return $this->contractVersion;
        }
    }

    $assert = static function (bool $condition, string $message): void {
        if (!$condition) throw new \RuntimeException($message);
    };
    $valid = new VacationAuthorizationSnapshot(
        true,
        4,
        ['PFK' => true, 'EB' => false],
        ['PFK' => false, 'EB' => true],
        [[
            'id' => 'eb-west',
            'label' => 'EB – West',
            'roleKey' => 'eb',
            'roleGroupId' => 'EB',
            'areaKey' => 'west',
            'areaGroupId' => 'Bereich-West',
        ]],
    );

    $logger = new VacationSettingsLogger();
    $service = new VacationSettingsUnderTest(new VacationSettingsApps(['localbase'], ['localbase']), $logger, true, $valid);
    $assert($service->status() === VacationSettingsService::VALID, 'Gültiger öffentlicher Urlaubsvertrag wird nicht erkannt.');
    $assert($service->enabledPeerEditGroups() === ['PFK'], 'Peer-Bearbeitung wird nicht getrennt projiziert.');
    $assert($service->enabledPeerApprovalGroups() === ['EB'], 'Peer-Genehmigung wird nicht getrennt projiziert.');
    $assert(array_column($service->ebAreaViews(), 'id') === ['eb-west'], 'Abgeleitete EB-Büroansicht fehlt.');

    foreach ([
        [new VacationSettingsApps([], []), true, $valid, VacationSettingsService::MISSING],
        [new VacationSettingsApps(['localbase'], []), true, $valid, VacationSettingsService::DISABLED],
        [new VacationSettingsApps(['localbase'], ['localbase']), false, $valid, VacationSettingsService::INCOMPATIBLE],
        [new VacationSettingsApps(['localbase'], ['localbase']), true, $valid, VacationSettingsService::INCOMPATIBLE, '2.0'],
        [new VacationSettingsApps(['localbase'], ['localbase']), true, new VacationAuthorizationSnapshot(false, 4, [], [], []), VacationSettingsService::INVALID],
    ] as $case) {
        [$apps, $available, $snapshot, $expectedStatus] = $case;
        $candidate = new VacationSettingsUnderTest($apps, new VacationSettingsLogger(), $available, $snapshot, $case[4] ?? '1.0');
        $assert($candidate->status() === $expectedStatus, "Providerzustand {$expectedStatus} wird nicht kontrolliert ausgewiesen.");
        $assert($candidate->enabledPeerEditGroups() === [], "Providerzustand {$expectedStatus} erteilt Bearbeitungsrechte.");
        $assert($candidate->enabledPeerApprovalGroups() === [], "Providerzustand {$expectedStatus} erteilt Genehmigungsrechte.");
        $assert($candidate->ebAreaViews() === [], "Providerzustand {$expectedStatus} liefert Ansichten.");
    }

    $failedLogger = new VacationSettingsLogger();
    $failed = new VacationSettingsUnderTest(
        new VacationSettingsApps(['localbase'], ['localbase']),
        $failedLogger,
        true,
        new \RuntimeException('synthetic provider failure'),
    );
    $assert($failed->status() === VacationSettingsService::UNAVAILABLE, 'Providerfehler wird nicht fail-closed behandelt.');
    $assert($failed->enabledPeerEditGroups() === [] && $failed->ebAreaViews() === [], 'Providerfehler gibt Rechte oder Ansichten frei.');
    $assert(count($failedLogger->warnings) === 1, 'Providerfehler wird nicht datensparsam diagnostiziert.');
    $failureContext = $failedLogger->warnings[0][1] ?? [];
    $assert(($failureContext['exceptionClass'] ?? null) === RuntimeException::class, 'Providerfehler bleibt nicht über die Exception-Klasse eingrenzbar.');
    $assert(!isset($failureContext['exception']), 'Providerfehler protokolliert ein vollständiges Exception-Objekt statt eines datensparsamen Kontexts.');

    echo "VacationSettingsServiceTest: OK\n";
}
