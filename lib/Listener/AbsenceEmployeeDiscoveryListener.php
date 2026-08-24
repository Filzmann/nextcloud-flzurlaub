<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Listener;

use OCA\AdUrlaub\Repository\VacationRepository;
use OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<AbsenceEmployeeDiscoveryEvent> */
final class AbsenceEmployeeDiscoveryListener implements IEventListener {
    public function __construct(private VacationRepository $vacations) {}

    public function handle(Event $event): void {
        if (!$event instanceof AbsenceEmployeeDiscoveryEvent) {
            return;
        }

        $timezone = $event->start()->getTimezone();
        $event->provide($this->vacations->findEmployeeUidsInRange(
            $event->start()->format('Y-m-d'),
            $event->end()->setTimezone($timezone)->modify('-1 second')->format('Y-m-d'),
        ));
    }
}
