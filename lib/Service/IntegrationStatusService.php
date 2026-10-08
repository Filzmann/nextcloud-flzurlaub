<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\LocalBase\Integration\FlzIntegrationCapabilities;
use OCA\LocalBase\Service\IntegrationCapabilityService;

/**
 * Zweck: Übersetzt den technischen Capability-Snapshot in einen kleinen Urlaubsplaner-Vertrag.
 * Vertrag: Fehlende Kalenderintegration ist ein zulässiger Standalone-Zustand; sie ändert keine Urlaubsrechte.
 */
final class IntegrationStatusService {
    public function __construct(private IntegrationCapabilityService $capabilities) {
    }

    /** @return array{available:bool,providers:list<string>} */
    public function calendarConflictCheck(): array {
        $key = FlzIntegrationCapabilities::SCHEDULE_CONFLICT_READ;
        $snapshot = $this->capabilities->query([$key]);
        $providers = $snapshot[$key] ?? [];

        return [
            'available' => $providers !== [],
            'providers' => $providers,
        ];
    }
}
