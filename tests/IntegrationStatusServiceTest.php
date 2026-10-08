<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventDispatcher { public function dispatchTyped(object $event): object; }
}

namespace {

    use OCA\FlzUrlaub\Service\IntegrationStatusService;
    use OCA\LocalBase\Integration\FlzIntegrationCapabilities;
    use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
    use OCA\LocalBase\Service\IntegrationCapabilityService;
    use OCP\EventDispatcher\IEventDispatcher;

    $dispatcher = new class implements IEventDispatcher {
        public bool $available = false;

        public function dispatchTyped(object $event): object {
            if ($this->available && $event instanceof IntegrationCapabilityQueryEvent) {
                $event->provide('flzcalendar', [FlzIntegrationCapabilities::SCHEDULE_CONFLICT_READ]);
            }
            return $event;
        }
    };
    $status = new IntegrationStatusService(new IntegrationCapabilityService($dispatcher));

    if ($status->calendarConflictCheck() !== [
        'available' => false,
        'providers' => [],
    ]) {
        throw new RuntimeException('Der Standalone-Status ohne Kalender ist falsch.');
    }

    $dispatcher->available = true;
    if ($status->calendarConflictCheck() !== [
        'available' => true,
        'providers' => ['flzcalendar'],
    ]) {
        throw new RuntimeException('Der integrierte Kalenderstatus ist falsch.');
    }

    echo "Filzmann Urlaubsplanung integration status test passed\n";
}
