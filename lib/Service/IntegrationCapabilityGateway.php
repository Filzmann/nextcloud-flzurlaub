<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\FlzOrganization\PublicApi\V1\IntegrationCapabilities;
use OCA\FlzOrganization\PublicApi\V1\IntegrationCapabilityQueryEvent;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Zweck: Liefert pro Anfrage einen Snapshot der aktuell angebotenen optionalen Integrationsfähigkeiten.
 * Vertrag: Ein leerer Snapshot ist ein regulärer Standalone-Zustand und kein Fehler.
 */
final class IntegrationCapabilityGateway {
    public function __construct(private IEventDispatcher $events) {
    }

    /**
     * @param list<string> $capabilities
     * @return array<string,list<string>>
     */
    public function query(array $capabilities): array {
        $event = new IntegrationCapabilityQueryEvent($capabilities);
        $this->events->dispatchTyped($event);
        return $event->available();
    }
}
